<?php

declare(strict_types=1);

namespace Unquam\NetteMaker\Migration\Drivers;

class SQLiteDriver implements DriverInterface
{
    public function compileCreateMigrationsTable(string $table): string
    {
        return "CREATE TABLE IF NOT EXISTS \"{$table}\" (
    \"id\" INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    \"migration\" VARCHAR(255) NOT NULL,
    \"ran_at\" DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);";
    }

    public function compileCreate(string $table, array $columns): string
    {
        $cleanedColumns = array_map(function (string $col) {
            return rtrim(trim($col), ';,');
        }, $columns);

        $cleanedColumns = array_filter($cleanedColumns);
        $columnsSql = implode(",\n    ", $cleanedColumns);

        return "CREATE TABLE \"{$table}\" (\n    {$columnsSql}\n);";
    }

    public function compileDrop(string $table): string
    {
        return "DROP TABLE \"{$table}\";";
    }

    public function compileDropIfExists(string $table): string
    {
        return "DROP TABLE IF EXISTS \"{$table}\";";
    }

    public function compileId(): string
    {
        return '"id" INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT';
    }

    public function compileString(string $column, int $length): string
    {
        return "\"{$column}\" VARCHAR({$length}) NOT NULL";
    }

    public function compileInteger(string $column): string
    {
        return "\"{$column}\" INTEGER NOT NULL";
    }

    public function compileBigInteger(string $column): string
    {
        return "\"{$column}\" INTEGER NOT NULL";
    }

    public function compileBoolean(string $column): string
    {
        return "\"{$column}\" INTEGER NOT NULL";
    }

    public function compileText(string $column): string
    {
        return "\"{$column}\" TEXT NOT NULL";
    }

    public function compileFloat(string $column): string
    {
        return "\"{$column}\" REAL NOT NULL";
    }

    public function compileDecimal(string $column, int $precision, int $scale): string
    {
        return "\"{$column}\" NUMERIC({$precision}, {$scale}) NOT NULL";
    }

    public function compileTimestamp(string $column): string
    {
        return "\"{$column}\" DATETIME NULL DEFAULT NULL";
    }

    public function compileNullable(string $column): string
    {
        return (string) preg_replace('/NOT NULL/', 'NULL', $column, 1);
    }

    /**
     * @param string $column
     * @param mixed $value
     */
    public function compileDefault(string $column, $value): string
    {
        if (is_bool($value)) {
            $compiled = $value ? '1' : '0';
        } elseif (is_string($value)) {
            $compiled = "'{$value}'";
        } else {
            $compiled = $value;
        }

        if (strpos($column, 'DEFAULT') !== false) {
            return (string) preg_replace('/DEFAULT \S+/', "DEFAULT {$compiled}", $column, 1);
        }

        return rtrim($column) . " DEFAULT {$compiled}";
    }

    public function compileUnique(string $column): string
    {
        return rtrim($column) . ' UNIQUE';
    }

    public function compileIndex(string $table, array $columns, string $name): string
    {
        $cols = implode('", "', $columns);

        // Inline layout format matching SQLite schema boundaries
        return "UNIQUE (\"{$cols}\")";
    }

    public function compileDropIndex(string $table, string $name): string
    {
        return "DROP INDEX \"{$name}\";";
    }

    public function compilePrimary(string $table, array $columns): string
    {
        $cols = implode('", "', $columns);
        return "PRIMARY KEY (\"{$cols}\")";
    }

    public function compileDropPrimary(string $table): string
    {
        // SQLite does not support ALTER TABLE DROP PRIMARY KEY natively
        return '';
    }

    public function compileForeign(string $table, string $column, string $referencedTable, string $referencedColumn, string $name, string $onDelete, string $onUpdate): string
    {
        // Inline key matching requirements for isolated SQLite schemas
        return "FOREIGN KEY (\"{$column}\") REFERENCES \"{$referencedTable}\" (\"{$referencedColumn}\") ON DELETE {$onDelete} ON UPDATE {$onUpdate}";
    }

    public function compileDropForeign(string $table, string $name): string
    {
        // SQLite does not support ALTER TABLE DROP FOREIGN KEY natively
        return '';
    }

    public function compileDropColumn(string $table, string $column): string
    {
        return "ALTER TABLE \"{$table}\" DROP COLUMN \"{$column}\";";
    }

    public function compileAfter(string $column, string $afterColumn): string
    {
        // SQLite does not natively support column positioning.
        // Returns an empty string to keep the core statement stable via TableBuilder.
        return '';
    }
}