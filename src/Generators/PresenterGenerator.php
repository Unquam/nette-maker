<?php

declare(strict_types=1);

namespace Unquam\NetteMaker\Generators;

use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\Printer;
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

        $class = $namespace->addClass($name . 'Presenter');
        $class->setExtends('Nette\Application\UI\Presenter');
        $class->setFinal();

        $method = $class->addMethod('renderDefault')
            ->setReturnType('void');

        $method->addBody('$this->template->title = ?;', [$name . ' Module']);
        $method->addBody('$this->template->description = \'Welcome to your newly generated component!\';');

        $printerClass = class_exists('Nette\PhpGenerator\PsrPrinter')
            ? 'Nette\PhpGenerator\PsrPrinter'
            : 'Nette\PhpGenerator\Printer';

        /** @var \Nette\PhpGenerator\Printer $printer */
        $printer = new $printerClass();

        return $printer->printFile($file);
    }
}