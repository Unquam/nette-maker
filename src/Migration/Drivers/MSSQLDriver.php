<?php

declare(strict_types=1);

namespace Unquam\NetteMaker\Migration\Drivers;

class MSSQLDriver implements DriverInterface
{
    public function compileCreateMigrationsTable(string $table): string
    {
        return "IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='{$table}' AND xtype='U')
CREATE TABLE [{$table}] (
    [id] BIGINT NOT NULL IDENTITY(1,1) PRIMARY KEY,
    [migration] NVARCHAR(255) NOT NULL,
    [ran_at] DATETIME2 NOT NULL DEFAULT GETDATE()
);";
    }

    public function compileCreate(string $table, array $columns): string
    {
        $cleanedColumns = array_map(function (string $col) {
            return rtrim(trim($col), ',');
        }, $columns);

        $cleanedColumns = array_filter($cleanedColumns);

        $columnsSql = implode(",\n    ", $cleanedColumns);

        return "CREATE TABLE [{$table}] (\n    {$columnsSql}\n);";
    }

    public function compileDrop(string $table): string
    {
        return "DROP TABLE [{$table}];";
    }

    public function compileDropIfExists(string $table): string
    {
        return "IF OBJECT_ID(N'[{$table}]', N'U') IS NOT NULL DROP TABLE [{$table}];";
    }

    public function compileId(): string
    {
        return '[id] BIGINT NOT NULL IDENTITY(1,1) PRIMARY KEY';
    }

    public function compileString(string $column, int $length): string
    {
        return "[{$column}] NVARCHAR({$length}) NOT NULL";
    }

    public function compileInteger(string $column): string
    {
        return "[{$column}] INT NOT NULL";
    }

    public function compileBigInteger(string $column): string
    {
        return "[{$column}] BIGINT NOT NULL";
    }

    public function compileBoolean(string $column): string
    {
        return "[{$column}] BIT NOT NULL";
    }

    public function compileText(string $column): string
    {
        return "[{$column}] NVARCHAR(MAX) NOT NULL";
    }

    public function compileFloat(string $column): string
    {
        return "[{$column}] FLOAT NOT NULL";
    }

    public function compileDecimal(string $column, int $precision, int $scale): string
    {
        return "[{$column}] DECIMAL({$precision}, {$scale}) NOT NULL";
    }

    public function compileTimestamp(string $column): string
    {
        return "[{$column}] DATETIME2 NULL DEFAULT NULL";
    }

    public function compileNullable(string $column): string
    {
        return preg_replace('/NOT NULL/', 'NULL', $column, 1);
    }

    /**
     * @param string $column
     * @param mixed $value
     * @return string
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
            return preg_replace('/DEFAULT \S+/', "DEFAULT {$compiled}", $column, 1);
        }

        return rtrim($column) . " DEFAULT {$compiled}";
    }

    public function compileUnique(string $column): string
    {
        return rtrim($column) . ' UNIQUE';
    }
}