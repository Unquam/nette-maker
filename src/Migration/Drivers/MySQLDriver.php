<?php

declare(strict_types=1);

namespace Unquam\NetteMaker\Migration\Drivers;

class MySQLDriver implements DriverInterface
{
    public function compileCreateMigrationsTable(string $table): string
    {
        return "CREATE TABLE IF NOT EXISTS `{$table}` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `migration` VARCHAR(255) NOT NULL,
    `ran_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    }

    public function compileCreate(string $table, array $columns): string
    {
        $columnsSql = implode(",\n    ", $columns);
        return "CREATE TABLE `{$table}` (\n    {$columnsSql}\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    }

    public function compileDrop(string $table): string
    {
        return "DROP TABLE `{$table}`;";
    }

    public function compileDropIfExists(string $table): string
    {
        return "DROP TABLE IF EXISTS `{$table}`;";
    }

    public function compileId(): string
    {
        return '`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY';
    }

    public function compileString(string $column, int $length): string
    {
        return "`{$column}` VARCHAR({$length}) NOT NULL";
    }

    public function compileInteger(string $column): string
    {
        return "`{$column}` INT NOT NULL";
    }

    public function compileBigInteger(string $column): string
    {
        return "`{$column}` BIGINT NOT NULL";
    }

    public function compileBoolean(string $column): string
    {
        return "`{$column}` TINYINT(1) NOT NULL DEFAULT 0";
    }

    public function compileText(string $column): string
    {
        return "`{$column}` TEXT NOT NULL";
    }

    public function compileFloat(string $column): string
    {
        return "`{$column}` FLOAT NOT NULL";
    }

    public function compileDecimal(string $column, int $precision, int $scale): string
    {
        return "`{$column}` DECIMAL({$precision}, {$scale}) NOT NULL";
    }

    public function compileTimestamp(string $column): string
    {
        return "`{$column}` TIMESTAMP NULL DEFAULT NULL";
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