<?php

declare(strict_types=1);

namespace Unquam\NetteMaker\Migration;

use Unquam\NetteMaker\Migration\Drivers\DriverInterface;

class TableBuilder
{
    /** @var DriverInterface */
    private $driver;

    /** @var array<string> */
    private $columns = [];

    /** @var array<string> */
    private $statements = [];

    /** @var string */
    private $currentTable = '';

    public function __construct(DriverInterface $driver)
    {
        $this->driver = $driver;
    }

    public function create(string $table, callable $callback): void
    {
        $this->currentTable = $table;
        $callback($this);
        $this->statements[] = $this->driver->compileCreate($table, $this->flushColumns());
    }

    public function table(string $table, callable $callback): void
    {
        $this->currentTable = $table;
        $callback($this);

        foreach ($this->flushColumns() as $statement) {
            $this->statements[] = $statement;
        }
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

    /**
     * @param string|array<string> $columns
     */
    public function index($columns, ?string $name = null): self
    {
        $columnsArray = (array) $columns;

        if ($name === null) {
            $name = 'idx_' . $this->currentTable . '_' . implode('_', $columnsArray);
        }

        $this->columns[] = $this->driver->compileIndex($this->currentTable, $columnsArray, $name);
        return $this;
    }

    /**
     * @param string|array<string> $columns
     */
    public function primary($columns): self
    {
        $columnsArray = (array) $columns;
        $this->columns[] = $this->driver->compilePrimary($this->currentTable, $columnsArray);
        return $this;
    }

    public function foreign(
        string $column,
        string $referencedTable,
        string $referencedColumn = 'id',
        ?string $name = null,
        string $onDelete = 'RESTRICT',
        string $onUpdate = 'RESTRICT'
    ): self {
        if ($name === null) {
            $name = 'fk_' . $this->currentTable . '_' . $column;
        }

        $this->columns[] = $this->driver->compileForeign(
            $this->currentTable,
            $column,
            $referencedTable,
            $referencedColumn,
            $name,
            $onDelete,
            $onUpdate
        );

        return $this;
    }

    public function cascadeOnDelete(): self
    {
        if (empty($this->columns)) {
            return $this;
        }

        $lastStatement = array_pop($this->columns);

        if (strpos($lastStatement, 'ON DELETE RESTRICT') !== false) {
            $lastStatement = str_replace('ON DELETE RESTRICT', 'ON DELETE CASCADE', $lastStatement);
        } elseif (strpos($lastStatement, 'ON DELETE') === false) {
            $lastStatement .= ' ON DELETE CASCADE';
        } else {
            $lastStatement = (string) preg_replace('/ON DELETE \w+/', 'ON DELETE CASCADE', $lastStatement);
        }

        $this->columns[] = $lastStatement;
        return $this;
    }

    public function after(string $afterColumn): self
    {
        if (empty($this->columns)) {
            return $this;
        }

        $last = array_pop($this->columns);
        $result = $this->driver->compileAfter($last, $afterColumn);
        $this->columns[] = $result !== '' ? $result : $last;
        return $this;
    }

    public function dropColumn(string $column): self
    {
        $this->columns[] = $this->driver->compileDropColumn($this->currentTable, $column);
        return $this;
    }

    public function dropIndex(string $name): self
    {
        $this->columns[] = $this->driver->compileDropIndex($this->currentTable, $name);
        return $this;
    }

    public function dropForeign(string $name): self
    {
        $this->columns[] = $this->driver->compileDropForeign($this->currentTable, $name);
        return $this;
    }

    public function dropPrimary(): self
    {
        $this->columns[] = $this->driver->compileDropPrimary($this->currentTable);
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
        $this->currentTable = '';
        return $columns;
    }
}