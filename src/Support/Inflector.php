<?php

declare(strict_types=1);

namespace Unquam\NetteMaker\Support;

use Doctrine\Inflector\Inflector as DoctrineInflector;
use Doctrine\Inflector\InflectorFactory;

class Inflector
{
    private static $inflector = null;

    private static function inflector(): DoctrineInflector
    {
        if (self::$inflector === null) {
            self::$inflector = InflectorFactory::create()->build();
        }

        return self::$inflector;
    }

    public static function toClassName(string $name): string
    {
        $name = self::toSnakeCase($name);
        $name = self::inflector()->singularize($name);
        return self::toPascalCase($name);
    }

    public static function toTableName(string $name): string
    {
        $name = self::toPascalCase($name);
        $name = self::toSnakeCase($name);
        return self::inflector()->pluralize($name);
    }

    public static function toPascalCase(string $name): string
    {
        $name = str_replace(['-', '_'], ' ', $name);
        return str_replace(' ', '', ucwords($name));
    }

    public static function toSnakeCase(string $name): string
    {
        $name = str_replace('-', '_', $name);
        $name = (string) preg_replace('/([a-z])([A-Z])/', '$1_$2', $name);
        $name = (string) preg_replace('/([A-Z]+)([A-Z][a-z])/', '$1_$2', $name);
        return strtolower($name);
    }
}
