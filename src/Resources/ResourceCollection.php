<?php

declare(strict_types=1);

namespace Unquam\NetteMaker\Resources;

use Nette\Database\Table\Selection;

abstract class ResourceCollection
{
    /** @var mixed */
    protected $resource;

    /**
     * @param mixed $resource Array, Iterator or Selection query stream
     */
    public function __construct($resource)
    {
        $this->resource = $resource;
    }

    /**
     * Define which single JsonResource class should be used to map items inside this collection.
     */
    abstract protected function collectWith(): string;

    /**
     * Universal factory handling collection streams with automatic pagination metadata tracking.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        if ($this->resource === null) {
            return ['data' => []];
        }

        $data = [];
        $resourceClass = $this->collectWith();

        // If it is a Nette Database Selection query stream, fetch rows securely via clean clone
        $items = $this->resource instanceof Selection ? (clone $this->resource)->fetchAll() : $this->resource;

        foreach ($items as $item) {
            /** @var JsonResource $instance */
            $instance = new $resourceClass($item);
            $data[] = $instance->toArray();
        }

        $result = ['data' => $data];

        // Extract pagination metadata if the collection is an instance of Nette Selection
        if ($this->resource instanceof Selection) {
            $limit = $this->resource->getSqlBuilder()->getLimit();
            $offset = $this->resource->getSqlBuilder()->getOffset();

            if ($limit !== null) {
                $totalCount = $this->resource->count('*');
                $perPage = (int) $limit;
                $currentOffset = $offset !== null ? (int) $offset : 0;
                $currentPage = (int) floor($currentOffset / $perPage) + 1;
                $lastPage = (int) ceil($totalCount / $perPage);

                $result['meta'] = [
                    'current_page' => $currentPage,
                    'per_page'     => $perPage,
                    'last_page'    => $lastPage,
                    'total'        => $totalCount,
                    'from'         => $totalCount > 0 ? $currentOffset + 1 : null,
                    'to'           => $totalCount > 0 ? min($currentOffset + $perPage, $totalCount) : null,
                ];
            }
        }

        return $result;
    }

    /**
     * Static helper manager layout matching standard Laravel collection invocation syntax style.
     *
     * @param mixed $resource
     * @return array<string, mixed>
     */
    public static function make($resource): array
    {
        $instance = new static($resource);
        return $instance->toArray();
    }
}