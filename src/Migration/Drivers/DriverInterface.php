<?php

declare(strict_types=1);

namespace Unquam\NetteMaker\Migration\Drivers;

interface DriverInterface
{
    /**
     * Return a CREATE TABLE statement for the migrations tracking table.
     */
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
}