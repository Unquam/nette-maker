<?php

declare(strict_types=1);

namespace Unquam\NetteMaker\Tests\Generators;

use PHPUnit\Framework\TestCase;
use Unquam\NetteMaker\Generators\PresenterGenerator;

class PresenterGeneratorTest extends TestCase
{
    /** @var string */
    private $testDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testDir = dirname(__DIR__, 2) . '/temp_tests';
    }

    protected function tearDown(): void
    {
        $targetDir = $this->testDir . '/app/Presentation/User';
        if (is_dir($targetDir)) {
            foreach ((array) glob($targetDir . '/*') as $file) {
                if (is_file($file)) unlink($file);
            }
            rmdir($targetDir);
            rmdir(dirname($targetDir)); // Presentation
            rmdir(dirname($targetDir, 2)); // app
            rmdir($this->testDir);
        }
        parent::tearDown();
    }

    public function testItGeneratesPresenterInFeatureFolderSuccessfully(): void
    {
        $generator = new PresenterGenerator($this->testDir);

        $generatedFile = $generator->generate('User');

        $expectedPath = $this->testDir . '/app/Presentation/User/UserPresenter.php';
        $this->assertSame($expectedPath, $generatedFile);
        $this->assertFileExists($generatedFile);

        $content = (string) file_get_contents($generatedFile);
        $this->assertStringContainsString('namespace App\Presentation\User;', $content);
        $this->assertStringContainsString('class UserPresenter', $content);
    }
}
