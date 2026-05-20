<?php

declare(strict_types=1);

namespace Unquam\NetteMaker\Generators;

use Unquam\NetteMaker\Exceptions\GeneratorException;
use Unquam\NetteMaker\Support\Inflector;

class RequestGenerator
{
    /** @var string */
    private $basePath;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/');
    }

    public function generate(string $module, string $requestName, bool $isWeb): string
    {
        $moduleFolder = Inflector::toClassName($module);
        $className = Inflector::toClassName($requestName);

        // Force add "Request" suffix if not present (e.g. "Store" -> "StoreRequest")
        if (substr($className, -7) !== 'Request') {
            $className .= 'Request';
        }

        // Build explicit modular path structure
        $rootFolder = $isWeb ? 'app/Presentation/Requests' : 'app/Presentation/Api/Requests';
        $dir = $this->basePath . '/' . $rootFolder . '/' . $moduleFolder;
        $file = $dir . '/' . $className . '.php';

        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new GeneratorException('Failed to create directory: ' . $dir);
        }

        if (file_exists($file)) {
            throw new GeneratorException('Request file already exists: ' . $file);
        }

        $stubFile = $isWeb ? 'request_web.stub' : 'request_api.stub';
        $stubPath = dirname(__DIR__, 2) . '/stubs/' . $stubFile;

        if (!file_exists($stubPath)) {
            throw new GeneratorException('Request stub layout file not found: ' . $stubPath);
        }

        $content = (string) file_get_contents($stubPath);

        // Dynamically replace both namespace Module name and Class name placeholders
        $content = str_replace('{{module}}', $moduleFolder, $content);
        $content = str_replace('{{name}}', $className, $content);

        if (file_put_contents($file, $content) === false) {
            throw new GeneratorException('Failed to write file: ' . $file);
        }

        return $file;
    }
}