<?php

declare(strict_types=1);

namespace Unquam\NetteMaker\Generators;

use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PsrPrinter;
use Unquam\NetteMaker\Exceptions\GeneratorException;

class ModelGenerator
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
        $dir = $this->basePath . '/app/Model';
        $file = $dir . '/' . $name . '.php';

        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
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


        $class->addMethod('__construct')
            ->setPublic()
            ->addPromotedParameter('explorer')
            ->setPrivate()
            ->setType('Explorer');

        return $this->printer->printFile($file);
    }
}