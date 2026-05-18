<?php

declare(strict_types=1);

namespace Unquam\NetteMaker\Generators;

use Nette\PhpGenerator\PhpFile;
use Unquam\NetteMaker\Exceptions\GeneratorException;

class PresenterGenerator
{
    /** @var string */
    private $basePath;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/');
    }

    public function generate(string $name): string
    {
        $dir = $this->basePath . '/app/Presentation/' . $name;
        $file = $dir . '/' . $name . 'Presenter.php';

        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new GeneratorException('Failed to create directory: ' . $dir);
        }

        if (file_exists($file)) {
            throw new GeneratorException('Presenter already exists: ' . $file);
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

        $namespace = $file->addNamespace('App\Presentation\\' . $name);
        $namespace->addUse('Nette\Application\UI\Presenter');

        $class = $namespace->addClass($name . 'Presenter');
        $class->setExtends('Nette\Application\UI\Presenter');
        $class->setFinal();

        $class->addMethod('renderDefault')
            ->setReturnType('void');

        return (string) $file;
    }
}