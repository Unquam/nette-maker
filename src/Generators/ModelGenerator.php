<?php

declare(strict_types=1);

namespace Unquam\NetteMaker\Generators;

use Nette\PhpGenerator\PhpFile;
use Unquam\NetteMaker\Exceptions\GeneratorException;

class ModelGenerator
{
    /** @var string */
    private $basePath;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/');
    }

    public function generate(string $name): string
    {
        $dir = $this->basePath . '/app/Model';
        $file = $dir . '/' . $name . '.php';

        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new GeneratorException('Failed to create directory: ' . $dir);
        }

        if (file_exists($file)) {
            throw new GeneratorException('Model already exists: ' . $file);
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

        $namespace = $file->addNamespace('App\Model');
        $namespace->addUse('Nette\Database\Explorer');

        $class = $namespace->addClass($name);
        $class->setFinal();

        $class->addProperty('explorer')
            ->setPrivate()
            ->setType('Nette\Database\Explorer');

        $constructor = $class->addMethod('__construct')
            ->setPublic();

        $constructor->addParameter('explorer')
            ->setType('Nette\Database\Explorer');

        $constructor->addBody('$this->explorer = $explorer;');

        return (string) $file;
    }
}