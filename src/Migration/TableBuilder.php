<?php

declare(strict_types=1);

namespace Unquam\NetteMaker\Migration;

use Unquam\NetteMaker\Migration\Drivers\DriverInterface;

class TableBuilder
{
    private DriverInterface $driver;
    private array $columns = [];
    private array $statements = [];

    public function __construct(DriverInterface $driver)
    {
        $this->driver = $driver;
    }

    public function create(string $table, callable $callback): void
    {
        $callback($this);
        $this->statements[] = $this->driver->compileCreate($table, $this->flushColumns());
    }

    public function drop(string $table): void
    {
        $this->statements[] = $this->driver->compileDrop($table);
    }

    public function dropIfExists(string $table): void
    {
        $this->statements[] = $this->driver->compileDropIfExists($table);
    }

    public function id(): self
    {
        $this->columns[] = $this->driver->compileId();
        return $this;
    }

    public function string(string $column, int $length = 255): self
    {
        $this->columns[] = $this->driver->compileString($column, $length);
        return $this;
    }

    public function integer(string $column): self
    {
        $this->columns[] = $this->driver->compileInteger($column);
        return $this;
    }

    public function bigInteger(string $column): self
    {
        $this->columns[] = $this->driver->compileBigInteger($column);
        return $this;
    }

    public function boolean(string $column): self
    {
        $this->columns[] = $this->driver->compileBoolean($column);
        return $this;
    }

    public function text(string $column): self
    {
        $this->columns[] = $this->driver->compileText($column);
        return $this;
    }

    public function float(string $column): self
    {
        $this->columns[] = $this->driver->compileFloat($column);
        return $this;
    }

    public function decimal(string $column, int $precision = 8, int $scale = 2): self
    {
        $this->columns[] = $this->driver->compileDecimal($column, $precision, $scale);
        return $this;
    }

    public function timestamp(string $column): self
    {
        $this->columns[] = $this->driver->compileTimestamp($column);
        return $this;
    }

    public function timestamps(): self
    {
        $this->columns[] = $this->driver->compileTimestamp('created_at');
        $this->columns[] = $this->driver->compileTimestamp('updated_at');
        return $this;
    }

    public function nullable(): self
    {
        $last = array_pop($this->columns);
        $this->columns[] = $this->driver->compileNullable($last);
        return $this;
    }

    /**
     * @param mixed $value
     */
    public function default($value): self
    {
        $last = array_pop($this->columns);
        $this->columns[] = $this->driver->compileDefault($last, $value);
        return $this;
    }

    public function unique(): self
    {
        $last = array_pop($this->columns);
        $this->columns[] = $this->driver->compileUnique($last);
        return $this;
    }

    public function getStatements(): array
    {
        return $this->statements;
    }

    private function flushColumns(): array
    {
        $columns = $this->columns;
        $this->columns = [];
        return $columns;
    }
}