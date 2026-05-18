<?php

declare(strict_types=1);

namespace Unquam\NetteMaker\Generators;

use Nette\PhpGenerator\PhpFile;
use Unquam\NetteMaker\Exceptions\GeneratorException;

class ServiceGenerator
{
    /** @var string */
    private $basePath;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/');
    }

    public function generate(string $name): string
    {
        $dir = $this->basePath . '/app/Model/Services';
        $file = $dir . '/' . $name . 'Service.php';

        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
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

        $repositoryClass = 'App\Model\Repositories\\' . $name . 'Repository';

        $namespace = $file->addNamespace('App\Model\Services');
        $namespace->addUse($repositoryClass);

        $class = $namespace->addClass($name . 'Service');
        $class->setFinal();

        // 1. Declare the class property explicitly for PHP 7.4 compatibility
        $class->addProperty('repository')
            ->setPrivate()
            ->setType($repositoryClass);

        // 2. Build a standard constructor with dependency assignment in the method body
        $constructor = $class->addMethod('__construct')
            ->setPublic();

        $constructor->addParameter('repository')
            ->setType($repositoryClass);

        $constructor->addBody('$this->repository = $repository;');

        return (string) $file;
    }
}