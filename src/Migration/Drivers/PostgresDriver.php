<?php

declare(strict_types=1);

namespace Unquam\NetteMaker\Migration\Drivers;

class PostgresDriver implements DriverInterface
{
    public function compileCreateMigrationsTable(string $table): string
    {
        return "CREATE TABLE IF NOT EXISTS \"{$table}\" (
    \"id\" BIGSERIAL PRIMARY KEY,
    \"migration\" VARCHAR(255) NOT NULL,
    \"ran_at\" TIMESTAMP NOT NULL DEFAULT NOW()
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
        return '"id" BIGSERIAL PRIMARY KEY';
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
        return "\"{$column}\" BIGINT NOT NULL";
    }

    public function compileBoolean(string $column): string
    {
        return "\"{$column}\" BOOLEAN NOT NULL";
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
        return "\"{$column}\" TIMESTAMP NULL DEFAULT NULL";
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
            $compiled = $value ? 'TRUE' : 'FALSE';
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

        // Inline declaration variant for Postgres table context blocks
        return "CONSTRAINT \"{$name}\" UNIQUE (\"{$cols}\")";
    }

    public function compileDropIndex(string $table, string $name): string
    {
        return "DROP INDEX \"{$name}\";";
    }

    public function compilePrimary(string $table, array $columns): string
    {
        $cols = implode('", "', $columns);

        // Inline composite primary key specification statement
        return "PRIMARY KEY (\"{$cols}\")";
    }

    public function compileDropPrimary(string $table): string
    {
        return "ALTER TABLE \"{$table}\" DROP CONSTRAINT \"{$table}_pkey\";";
    }

    public function compileForeign(string $table, string $column, string $referencedTable, string $referencedColumn, string $name, string $onDelete, string $onUpdate): string
    {
        return "CONSTRAINT \"{$name}\" FOREIGN KEY (\"{$column}\") REFERENCES \"{$referencedTable}\" (\"{$referencedColumn}\") ON DELETE {$onDelete} ON UPDATE {$onUpdate}";
    }

    public function compileDropForeign(string $table, string $name): string
    {
        return "ALTER TABLE \"{$table}\" DROP CONSTRAINT \"{$name}\";";
    }

    public function compileDropColumn(string $table, string $column): string
    {
        return "ALTER TABLE \"{$table}\" DROP COLUMN \"{$column}\";";
    }

    public function compileAfter(string $column, string $afterColumn): string
    {
        // PostgreSQL does not natively support column positioning.
        // Returning an empty string prevents string duplication bugs in TableBuilder.
        return '';
    }

    public function compileShowTables(): string
    {
        return "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_type = 'BASE TABLE' ORDER BY table_name;";
    }

    public function compileDisableForeignKeys(): string
    {
        return "SET CONSTRAINTS ALL DEFERRED;";
    }

    public function compileEnableForeignKeys(): string
    {
        return "SET CONSTRAINTS ALL IMMEDIATE;";
    }
}