<?php

declare(strict_types=1);

namespace Unquam\NetteMaker\Migration\Drivers;

interface DriverInterface
{
    public function compileCreateMigrationsTable(string $table): string;

    public function compileCreate(string $table, array $columns): string;

    public function compileDrop(string $table): string;

    public function compileDropIfExists(string $table): string;

    public function compileId(): string;

    public function compileString(string $column, int $length): string;

    public function compileInteger(string $column): string;

    public function compileBigInteger(string $column): string;

    public function compileBoolean(string $column): string;

    public function compileText(string $column): string;

    public function compileFloat(string $column): string;

    public function compileDecimal(string $column, int $precision, int $scale): string;

    public function compileTimestamp(string $column): string;

    public function compileNullable(string $column): string;

    /**
     * @param mixed $value
     */
    public function compileDefault(string $column, $value): string;

    public function compileUnique(string $column): string;

    // Index
    public function compileIndex(string $table, array $columns, string $name): string;

    public function compileDropIndex(string $table, string $name): string;

    // Primary key
    public function compilePrimary(string $table, array $columns): string;

    public function compileDropPrimary(string $table): string;

    // Foreign key
    public function compileForeign(string $table, string $column, string $referencedTable, string $referencedColumn, string $name, string $onDelete, string $onUpdate): string;

    public function compileDropForeign(string $table, string $name): string;

    // Drop column
    public function compileDropColumn(string $table, string $column): string;

    // After (MySQL/MariaDB only - other drivers return empty string)
    public function compileAfter(string $column, string $afterColumn): string;
}