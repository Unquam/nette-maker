<?php

declare(strict_types=1);

namespace Unquam\NetteMaker\Generators;

use Unquam\NetteMaker\Exceptions\GeneratorException;

class LatteGenerator
{
    /** @var string */
    private $basePath;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/');
    }

    public function generate(string $name): string
    {
        $dir = $this->basePath . '/app/Presentation/' . $name;
        $file = $dir . '/default.latte';

        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new GeneratorException('Failed to create directory: ' . $dir);
        }

        if (file_exists($file)) {
            throw new GeneratorException('Latte template already exists: ' . $file);
        }

        $content = "{block content}\n" .
            "<div id=\"content\">\n" .
            "    <h1>{$name} Module</h1>\n" .
            "    <p>Find me in app/Presentation/{$name}/default.latte</p>\n" .
            "</div>\n";

        if (file_put_contents($file, $content) === false) {
            throw new GeneratorException('Failed to write file: ' . $file);
        }

        return $file;
    }
}