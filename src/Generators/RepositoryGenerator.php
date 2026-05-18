<?php

declare(strict_types=1);

namespace Unquam\NetteMaker\Generators;

use Nette\PhpGenerator\PhpFile;
use Unquam\NetteMaker\Exceptions\GeneratorException;
use Unquam\NetteMaker\Support\Inflector;

class RepositoryGenerator
{
    /** @var string */
    private $basePath;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/');
    }

    public function generate(string $name): string
    {
        $dir = $this->basePath . '/app/Model/Repositories';
        $file = $dir . '/' . $name . 'Repository.php';

        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new GeneratorException('Failed to create directory: ' . $dir);
        }

        if (file_exists($file)) {
            throw new GeneratorException('Repository already exists: ' . $file);
        }

        $content = $this->buildClass($name);

        if (file_put_contents($file, $content) === false) {
            throw new GeneratorException('Failed to write file: ' . $file);
        }

        return $file;
    }

    private function buildClass(string $name): string
    {
        $file = new PhpFile();
        $file->setStrictTypes();

        $namespace = $file->addNamespace('App\Model\Repositories');
        $namespace->addUse('Nette\Database\Explorer');
        $namespace->addUse('Nette\Database\Table\Selection');

        $class = $namespace->addClass($name . 'Repository');
        $class->setFinal();

        $class->addConstant('TABLE', Inflector::toTableName($name))
            ->setPrivate();

        // 1. from PHP 7.4 - typed property
        $class->addProperty('explorer')
            ->setPrivate()
            ->setType('Nette\Database\Explorer');

        // 2. Constructor
        $constructor = $class->addMethod('__construct')
            ->setPublic();

        $constructor->addParameter('explorer')
            ->setType('Nette\Database\Explorer');

        $constructor->addBody('$this->explorer = $explorer;');

        // 3. Method findAll
        $class->addMethod('findAll')
            ->setPublic()
            ->setReturnType('Nette\Database\Table\Selection')
            ->setBody('return $this->explorer->table(self::TABLE);');

        // 4. Method findById
        $findById = $class->addMethod('findById')
            ->setPublic()
            ->setReturnType('mixed');  // No return type - mixed is PHP 8.0+ only

        $findById->addParameter('id')
            ->setType('int');

        $findById->setBody('return $this->explorer->table(self::TABLE)->get($id);');

        return (string) $file;
    }
}