<?php

declare(strict_types=1);

namespace Unquam\NetteMaker\Generators;

use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PsrPrinter;
use Unquam\NetteMaker\Exceptions\GeneratorException;

class ServiceGenerator
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
        $dir = $this->basePath . '/app/Model/Services';
        $file = $dir . '/' . $name . 'Service.php';

        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            throw new GeneratorException('Failed to create directory: ' . $dir);
        }

        if (file_exists($file)) {
            throw new GeneratorException('Service already exists: ' . $file);
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

        $namespace = $file->addNamespace('App\Model\Services');
        $namespace->addUse('App\Model\Repositories\\' . $name . 'Repository');

        $class = $namespace->addClass($name . 'Service');
        $class->setFinal();

        $class->addMethod('__construct')
            ->setPublic()
            ->addPromotedParameter('repository')
            ->setPrivate()
            ->setType($name . 'Repository');

        return $this->printer->printFile($file);
    }
}