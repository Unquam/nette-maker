<?php

declare(strict_types=1);

namespace Unquam\NetteMaker\Generators;

use Unquam\NetteMaker\Exceptions\GeneratorException;
use Unquam\NetteMaker\Support\Inflector;

class SeederGenerator
{
    /** @var string */
    private $seedersDir;

    public function __construct(string $seedersDir)
    {
        $this->seedersDir = rtrim($seedersDir, '/');
    }

    public function generate(string $name): string
    {
        $className = Inflector::toClassName($name);
        if (strpos($className, 'Seeder') === false) {
            $className .= 'Seeder';
        }

        $file = $this->seedersDir . '/' . $className . '.php';

        if (!is_dir($this->seedersDir) && !@mkdir($this->seedersDir, 0755, true) && !is_dir($this->seedersDir)) {
            throw new GeneratorException('Failed to create directory: ' . $this->seedersDir);
        }

        if (file_exists($file)) {
            throw new GeneratorException('Seeder already exists: ' . $file);
        }

        $content = $this->loadStub();

        if (file_put_contents($file, $content) === false) {
            throw new GeneratorException('Failed to write file: ' . $file);
        }

        return $file;
    }

    private function loadStub(): string
    {
        $stub = dirname(__DIR__, 2) . '/stubs/seeder.stub';

        if (!file_exists($stub)) {
            throw new GeneratorException('Stub file not found: ' . $stub);
        }

        return (string) file_get_contents($stub);
    }
}