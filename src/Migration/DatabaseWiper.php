<?php

declare(strict_types=1);

namespace Unquam\NetteMaker\Migration;

use Unquam\NetteMaker\Migration\Drivers\DriverInterface;

class DatabaseWiper
{
    /** @var \PDO */
    private $pdo;

    /** @var DriverInterface */
    private $driver;

    public function __construct(\PDO $pdo, DriverInterface $driver)
    {
        $this->pdo = $pdo;
        $this->driver = $driver;
    }

    /**
     * Completely drop all tables from the database.
     *
     * @return array<string> List of dropped table names
     */
    public function wipe(): array
    {
        $stmt = $this->pdo->query($this->driver->compileShowTables());
        $tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        if (empty($tables)) {
            return [];
        }

        try {
            $this->pdo->exec($this->driver->compileDisableForeignKeys());

            foreach ($tables as $table) {
                $this->pdo->exec($this->driver->compileDropIfExists((string) $table));
            }
        } finally {
            $this->pdo->exec($this->driver->compileEnableForeignKeys());
        }

        return array_map('strval', $tables);
    }
}