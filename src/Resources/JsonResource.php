<?php

declare(strict_types=1);

namespace Unquam\NetteMaker\Resources;

abstract class JsonResource
{
    /** @var mixed */
    protected $resource;

    /**
     * @param mixed $resource Single record item (e.g. ActiveRow or array)
     */
    public function __construct($resource)
    {
        $this->resource = $resource;
    }

    /**
     * Transform the single record item into an array mapping.
     *
     * @return array<string, mixed>
     */
    abstract public function toArray(): array;

    /**
     * Static helper resolving single item responses.
     *
     * @param mixed $resource
     * @return array<string, mixed>
     */
    public static function make($resource): array
    {
        if ($resource === null) {
            return ['data' => []];
        }

        $instance = new static($resource);
        return ['data' => $instance->toArray()];
    }
}