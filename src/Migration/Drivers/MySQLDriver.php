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
        $cleanedColumns = array_map(function (string $col) {
            return rtrim(trim($col), ';,'); // Cleared both commas and semicolons safely
        }, $columns);

        $cleanedColumns = array_filter($cleanedColumns);
        $columnsSql = implode(",\n    ", $cleanedColumns);

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
        return "`{$column}` TINYINT(1) NOT NULL";
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
        return (string) preg_replace('/NOT NULL/', 'NULL', $column, 1);
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
        $cols = implode('`, `', $columns);

        // Inline specification compatible with compileCreate table context block
        return "INDEX `{$name}` (`{$cols}`)";
    }

    public function compileDropIndex(string $table, string $name): string
    {
        return "ALTER TABLE `{$table}` DROP INDEX `{$name}`;";
    }

    public function compilePrimary(string $table, array $columns): string
    {
        $cols = implode('`, `', $columns);
        return "PRIMARY KEY (`{$cols}`)";
    }

    public function compileDropPrimary(string $table): string
    {
        return "ALTER TABLE `{$table}` DROP PRIMARY KEY;";
    }

    public function compileForeign(string $table, string $column, string $referencedTable, string $referencedColumn, string $name, string $onDelete, string $onUpdate): string
    {
        // Inline constraint definition seamlessly handled within the table compilation array
        return "CONSTRAINT `{$name}` FOREIGN KEY (`{$column}`) REFERENCES `{$referencedTable}` (`{$referencedColumn}`) ON DELETE {$onDelete} ON UPDATE {$onUpdate}";
    }

    public function compileDropForeign(string $table, string $name): string
    {
        return "ALTER TABLE `{$table}` DROP FOREIGN KEY `{$name}`;";
    }

    public function compileDropColumn(string $table, string $column): string
    {
        return "ALTER TABLE `{$table}` DROP COLUMN `{$column}`;";
    }

    public function compileAfter(string $column, string $afterColumn): string
    {
        // Handled cleanly within modifiers, returns string suffix statement
        return "AFTER `{$afterColumn}`";
    }

    // Add inside MySQLDriver.php:
    public function compileShowTables(): string
    {
        return 'SHOW TABLES';
    }

    public function compileDisableForeignKeys(): string
    {
        return 'SET FOREIGN_KEY_CHECKS = 0;';
    }

    public function compileEnableForeignKeys(): string
    {
        return 'SET FOREIGN_KEY_CHECKS = 1;';
    }
}