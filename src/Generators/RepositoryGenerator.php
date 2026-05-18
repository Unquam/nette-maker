<?php

declare(strict_types=1);

namespace Unquam\NetteMaker\Generators;

use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PsrPrinter;
use Unquam\NetteMaker\Exceptions\GeneratorException;
use Unquam\NetteMaker\Support\Inflector;

class RepositoryGenerator
{
    private string $basePath;
    private PsrPrinter $printer;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/');
        $this->printer = new PsrPrinter();
    }

    public function generate(string $name): string
    {
        $dir = $this->basePath . '/app/Model/Repositories';
        $file = $dir . '/' . $name . 'Repository.php';

        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
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

        $class->addMethod('__construct')
            ->setPublic()
            ->addPromotedParameter('explorer')
            ->setPrivate()
            ->setType('Explorer');

        $class->addMethod('findAll')
            ->setPublic()
            ->setReturnType('Selection')
            ->setBody('return $this->explorer->table(self::TABLE);');

        $class->addMethod('findById')
            ->setPublic()
            ->setReturnType('mixed')
            ->setBody('return $this->explorer->table(self::TABLE)->get($id);')
            ->addParameter('id')
            ->setType('int');

        return $this->printer->printFile($file);
    }
}