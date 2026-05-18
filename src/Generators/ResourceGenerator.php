<?php

declare(strict_types=1);

namespace Unquam\NetteMaker\Generators;

use Unquam\NetteMaker\Exceptions\GeneratorException;
use Unquam\NetteMaker\Support\Inflector;

class ResourceGenerator
{
    /** @var string */
    private $basePath;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/');
    }

    public function generate(string $name): string
    {
        $pureName = Inflector::toClassName($name);
        $dir = $this->basePath . '/app/Presentation/Api/Resources';

        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new GeneratorException('Failed to create directory: ' . $dir);
        }

        // Smart evaluation routing matching collection string suffixes
        if (substr($pureName, -10) === 'Collection') {
            $pureName = substr($pureName, 0, -10);
            $file = $dir . '/' . $pureName . 'Collection.php';
            $stubPath = dirname(__DIR__, 2) . '/stubs/resource_collection.stub';
        } else {
            if (substr($pureName, -8) === 'Resource') {
                $pureName = substr($pureName, 0, -8);
            }
            $file = $dir . '/' . $pureName . 'Resource.php';
            $stubPath = dirname(__DIR__, 2) . '/stubs/resource.stub';
        }

        if (file_exists($file)) {
            throw new GeneratorException('Resource element already exists: ' . $file);
        }

        if (!file_exists($stubPath)) {
            throw new GeneratorException('Resource stub layout file not found.');
        }

        $content = (string) file_get_contents($stubPath);
        $content = str_replace('{{name}}', $pureName, $content);

        if (file_put_contents($file, $content) === false) {
            throw new GeneratorException('Failed to write file: ' . $file);
        }

        return $file;
    }
}