<?php

declare(strict_types=1);

namespace Unquam\NetteMaker\Generators;

use Unquam\NetteMaker\Exceptions\GeneratorException;
use Unquam\NetteMaker\Support\Inflector;

class MigrationGenerator
{
    /** @var string */
    private $migrationsDir;

    public function __construct(string $migrationsDir)
    {
        $this->migrationsDir = rtrim($migrationsDir, '/');
    }

    public function generate(string $name): string
    {
        $normalizedName = Inflector::toSnakeCase($name);

        $filename = date('Y_m_d_His') . '_' . $normalizedName . '.php';
        $file = $this->migrationsDir . '/' . $filename;

        if (!is_dir($this->migrationsDir) && !@mkdir($this->migrationsDir, 0755, true) && !is_dir($this->migrationsDir)) {
            throw new GeneratorException('Failed to create directory: ' . $this->migrationsDir);
        }

        $content = $this->loadStub($normalizedName);

        if (file_put_contents($file, $content) === false) {
            throw new GeneratorException('Failed to write file: ' . $file);
        }

        return $file;
    }

    private function loadStub(string $name): string
    {
        $stub = dirname(__DIR__, 2) . '/stubs/migration.stub';

        if (!file_exists($stub)) {
            throw new GeneratorException('Stub file not found: ' . $stub);
        }

        $content = (string) file_get_contents($stub);

        if (preg_match('/^create_(.+)_table$/', $name, $matches)) {
            $tableName = $matches[1];
        } else {
            $tableName = Inflector::toTableName($name);
        }

        return str_replace('{{table}}', $tableName, $content);
    }
}