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
        $columnsSql = implode(",\n    ", $columns);
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
        return "\"{$column}\" INTEGER NOT NULL DEFAULT 0";
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
        return preg_replace('/NOT NULL/', 'NULL', $column, 1);
    }

    /**
     * @param mixed $value
     */
    public function compileDefault(string $column, $value): string
    {
        $compiled = is_string($value) ? "'{$value}'" : $value;
        return preg_replace('/DEFAULT \S+|$/', "DEFAULT {$compiled}", $column, 1);
    }

    public function compileUnique(string $column): string
    {
        return $column . ' UNIQUE';
    }
}