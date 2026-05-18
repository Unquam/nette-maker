<?php

declare(strict_types=1);

namespace Unquam\NetteMaker\Generators;

use Unquam\NetteMaker\Exceptions\GeneratorException;
use Unquam\NetteMaker\Support\Inflector;

class FactoryGenerator
{
    /** @var string */
    private $factoriesDir;

    public function __construct(string $factoriesDir)
    {
        $this->factoriesDir = rtrim($factoriesDir, '/');
    }

    public function generate(string $name): string
    {
        $pureName = Inflector::toClassName($name);

        if (substr($pureName, -7) === 'Factory') {
            $pureName = substr($pureName, 0, -7);
        }

        $className = $pureName . 'Factory';
        $file = $this->factoriesDir . '/' . $className . '.php';

        if (!is_dir($this->factoriesDir) && !@mkdir($this->factoriesDir, 0755, true) && !is_dir($this->factoriesDir)) {
            throw new GeneratorException('Failed to create directory: ' . $this->factoriesDir);
        }

        if (file_exists($file)) {
            throw new GeneratorException('Factory already exists: ' . $file);
        }

        $content = $this->loadStub($pureName);

        if (file_put_contents($file, $content) === false) {
            throw new GeneratorException('Failed to write file: ' . $file);
        }

        return $file;
    }

    private function loadStub(string $pureName): string
    {
        $stub = dirname(__DIR__, 2) . '/stubs/factory.stub';

        if (!file_exists($stub)) {
            throw new GeneratorException('Stub file not found: ' . $stub);
        }

        $content = (string) file_get_contents($stub);
        $tableName = Inflector::toTableName($pureName);

        $content = str_replace('{{name}}', $pureName, $content);
        return str_replace('{{table}}', $tableName, $content);
    }
}