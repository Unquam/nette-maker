<?php

declare(strict_types=1);

namespace Unquam\NetteMaker\Migration;

use Unquam\NetteMaker\Exceptions\MigrationException;

class SeederRunner
{
    /** @var \PDO */
    private $pdo;

    /** @var string */
    private $seedersPath;

    public function __construct(\PDO $pdo, string $seedersPath)
    {
        $this->pdo = $pdo;
        $this->seedersPath = rtrim($seedersPath, '/');
    }

    /**
     * Run all available seeders in alphabetical order.
     *
     * @return array<string> List of executed seeder filenames
     * @throws MigrationException
     */
    public function seed(): array
    {
        if (!is_dir($this->seedersPath)) {
            throw new MigrationException('Seeders directory not found: ' . $this->seedersPath);
        }

        $files = glob($this->seedersPath . '/*.php');
        if ($files === false || empty($files)) {
            return [];
        }

        sort($files);
        $executed = [];

        foreach ($files as $file) {
            $this->executeSeederFile($file);
            $executed[] = basename($file);
        }

        return $executed;
    }

    /**
     * Run a single specific seeder by its file name or class name notation.
     *
     * @param string $className Example: "UserSeeder" or "UserSeeder.php"
     * @throws MigrationException
     */
    public function seedOne(string $className): string
    {
        $filename = rtrim($className, '.php') . '.php';
        $fullPath = $this->seedersPath . '/' . $filename;

        if (!file_exists($fullPath)) {
            throw new MigrationException(sprintf('Seeder file not found: %s/ %s', basename($this->seedersPath), $filename));
        }

        $this->executeSeederFile($fullPath);

        return $filename;
    }

    /**
     * Shared isolated context execution logic runner block
     *
     * @throws MigrationException
     */
    private function executeSeederFile(string $file): void
    {
        $seeder = require $file;

        if (!is_object($seeder) || !method_exists($seeder, 'run')) {
            throw new MigrationException('Invalid seeder file format: ' . $file);
        }

        try {
            $seeder->run($this->pdo);
        } catch (\Throwable $e) {
            throw new MigrationException(sprintf('Seeder [%s] failed: %s', basename($file), $e->getMessage()), 0, $e);
        }
    }
}