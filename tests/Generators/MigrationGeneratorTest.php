<?php

declare(strict_types=1);

namespace Unquam\NetteMaker\Tests\Generators;

use PHPUnit\Framework\TestCase;
use Unquam\NetteMaker\Exceptions\GeneratorException;
use Unquam\NetteMaker\Generators\MigrationGenerator;

class MigrationGeneratorTest extends TestCase
{
    /** @var string */
    private $testDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testDir = dirname(__DIR__, 2) . '/temp_tests/migrations';
    }

    protected function tearDown(): void
    {
        if (is_dir($this->testDir)) {
            $files = glob($this->testDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->testDir);

            $parentDir = dirname($this->testDir);
            if (is_dir($parentDir) && count((array) glob($parentDir . '/*')) === 0) {
                rmdir($parentDir);
            }
        }

        parent::tearDown();
    }

    public function testItGeneratesMigrationFileSuccessfully(): void
    {
        $generator = new MigrationGenerator($this->testDir);
        $generatedFile = $generator->generate('create_users_table');

        $this->assertFileExists($generatedFile);
        $this->assertStringContainsString('create_users_table.php', $generatedFile);

        $content = (string) file_get_contents($generatedFile);

        $this->assertStringContainsString("'users'", $content);
        $this->assertStringNotContainsString('{{table}}', $content);
    }

    public function testItThrowsExceptionWhenDirectoryCannotBeCreated(): void
    {
        $this->expectException(GeneratorException::class);

        $invalidDir = '/root/forbidden/path/migrations';

        @$generator = new MigrationGenerator($invalidDir);
        $generator->generate('create_users_table');
    }
}
