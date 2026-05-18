<?php

declare(strict_types=1);

namespace Unquam\NetteMaker\Migration;

abstract class AbstractFactory
{
    /** @var \PDO */
    protected $pdo;

    /** @var string */
    protected $table;

    /** @var int */
    protected $amount = 1;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->table = $this->defineTable();
    }

    /**
     * Define the target database table name.
     */
    abstract protected function defineTable(): string;

    /**
     * Define the default model blueprint definitions definitions array.
     *
     * @return array<string, mixed>
     */
    abstract protected function definition(): array;

    /**
     * Set the amount of records to be generated fluently.
     */
    public function count(int $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    /**
     * Generate records and insert them directly into the database via PDO context.
     *
     * @param array<string, mixed> $attributes Override specific definition defaults
     * @return array<int> List of newly inserted record IDs
     */
    public function create(array $attributes = []): array
    {
        $insertedIds = [];

        for ($i = 0; $i < $this->amount; $i++) {
            $data = array_merge($this->definition(), $attributes);

            if (empty($data)) {
                continue;
            }

            $columns = array_keys($data);
            $placeholders = array_fill(0, count($data), '?');

            $sql = sprintf(
                'INSERT INTO `%s` (`%s`) VALUES (%s)',
                $this->table,
                implode('`, `', $columns),
                implode(', ', $placeholders)
            );

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(array_values($data));

            $insertedIds[] = (int) $this->pdo->lastInsertId();
        }

        $this->amount = 1;

        return $insertedIds;
    }
}