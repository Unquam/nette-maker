<?php

declare(strict_types=1);

namespace Unquam\NetteMaker\Migration;

use Unquam\NetteMaker\Exceptions\MigrationException;
use Unquam\NetteMaker\Migration\Drivers\DriverInterface;

class MigrationRunner
{
    /** @var \PDO */
    private $pdo;

    /** @var DriverInterface */
    private $driver;

    /** @var string */
    private $migrationsPath;

    /** @var string */
    private $migrationsTable = 'nette_migrations';

    public function __construct(\PDO $pdo, DriverInterface $driver, string $migrationsPath)
    {
        $this->pdo = $pdo;
        $this->driver = $driver;
        $this->migrationsPath = rtrim($migrationsPath, '/');
        $this->ensureMigrationsTable();
    }

    public function run(): array
    {
        $files = $this->getPendingMigrations();
        $ran = [];

        foreach ($files as $file) {
            $migration = require $file;

            if (!is_object($migration) || !method_exists($migration, 'up')) {
                throw new MigrationException('Invalid migration file: ' . $file);
            }

            $builder = new TableBuilder($this->driver);
            $migration->up($builder);

            try {
                // Executing statements sequentially (DDL implicitly commits in MySQL)
                foreach ($builder->getStatements() as $statement) {
                    $this->pdo->exec($statement);
                }

                $this->markAsRan($file);
                $ran[] = basename($file);
            } catch (\Throwable $e) {
                throw new MigrationException(sprintf('Migration [%s] failed: %s', basename($file), $e->getMessage()), 0, $e);
            }
        }

        return $ran;
    }

    public function rollback(): array
    {
        $files = $this->getRanMigrations();
        $rolledBack = [];

        foreach (array_reverse($files) as $file) {
            $fullPath = $this->migrationsPath . '/' . $file;

            if (!file_exists($fullPath)) {
                throw new MigrationException('Migration file not found: ' . $fullPath);
            }

            $migration = require $fullPath;

            if (!is_object($migration) || !method_exists($migration, 'down')) {
                throw new MigrationException('Invalid migration file: ' . $fullPath);
            }

            $builder = new TableBuilder($this->driver);
            $migration->down($builder);

            try {
                foreach ($builder->getStatements() as $statement) {
                    $this->pdo->exec($statement);
                }

                $this->markAsRolledBack($file);
                $rolledBack[] = $file;
            } catch (\Throwable $e) {
                throw new MigrationException(sprintf('Rollback of [%s] failed: %s', $file, $e->getMessage()), 0, $e);
            }
        }

        return $rolledBack;
    }

    public function status(): array
    {
        $all = $this->getAllMigrationFiles();
        $ran = $this->getRanMigrations();
        $status = [];

        foreach ($all as $file) {
            $name = basename($file);
            $status[] = [
                'migration' => $name,
                'ran' => in_array($name, $ran, true),
            ];
        }

        return $status;
    }

    private function ensureMigrationsTable(): void
    {
        $sql = $this->driver->compileCreateMigrationsTable($this->migrationsTable);
        $this->pdo->exec($sql);
    }

    private function getPendingMigrations(): array
    {
        $all = $this->getAllMigrationFiles();
        $ran = $this->getRanMigrations();

        return array_filter($all, function (string $file) use ($ran): bool {
            return !in_array(basename($file), $ran, true);
        });
    }

    private function getAllMigrationFiles(): array
    {
        if (!is_dir($this->migrationsPath)) {
            throw new MigrationException('Migrations directory not found: ' . $this->migrationsPath);
        }

        $files = glob($this->migrationsPath . '/*.php');
        sort($files);

        return $files ?: [];
    }

    private function getRanMigrations(): array
    {
        $stmt = $this->pdo->query("SELECT migration FROM {$this->migrationsTable} ORDER BY id ASC");
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    private function markAsRan(string $file): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO {$this->migrationsTable} (migration) VALUES (?)");
        $stmt->execute([basename($file)]);
    }

    private function markAsRolledBack(string $file): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->migrationsTable} WHERE migration = ?");
        $stmt->execute([$file]);
    }
}