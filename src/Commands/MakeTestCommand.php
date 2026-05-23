<?php
declare(strict_types=1);

namespace Unquam\NetteMaker\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MakeTestCommand extends Command
{
    protected static $defaultName = 'make:test';

    /** @var string */
    private $basePath;

    public function __construct(string $configFile)
    {
        parent::__construct('make:test');
        $this->basePath = dirname($configFile);
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Create a new test class for Nette Tester or PHPUnit')
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the class to test (e.g., Services/UserService)')
            ->addOption('phpunit', null, InputOption::VALUE_NONE, 'Generate a PHPUnit test instead of Nette Tester');
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        if ($input->getArgument('name') !== null) {
            return;
        }

        $io = new SymfonyStyle($input, $output);
        $name = $io->ask('Enter the name of the class to test (e.g., Services/UserService)');

        if (empty($name)) {
            $io->error('The class name cannot be empty.');
            exit(Command::FAILURE);
        }

        $input->setArgument('name', $name);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $name = (string) $input->getArgument('name');
        $isPhpUnit = (bool) $input->getOption('phpunit');

        if (!$isPhpUnit && !class_exists('Tester\Environment')) {
            $io->warning([
                'Nette Tester is not installed in this project!',
                'The test will be generated, but you won\'t be able to run it.',
                'Run: composer require --dev nette/tester'
            ]);
        }

        if ($isPhpUnit && !class_exists('PHPUnit\Framework\TestCase')) {
            $io->warning([
                'PHPUnit is not installed in this project!',
                'The test will be generated, but you won\'t be able to run it.',
                'Run: composer require --dev phpunit/phpunit'
            ]);
        }

        $name = str_replace('\\', '/', $name);
        $className = basename($name);
        $testClassName = $className . 'Test';
        $testedClassVar = lcfirst($className);

        $testedClassFqn = 'App\\' . str_replace('/', '\\', $name);

        if (!$isPhpUnit) {
            $bootstrapPath = $this->basePath . '/tests/bootstrap.php';
            if (!file_exists($bootstrapPath)) {
                $bootstrapDir = dirname($bootstrapPath);
                if (!is_dir($bootstrapDir)) {
                    mkdir($bootstrapDir, 0777, true);
                }

                $bootstrapContent = "<?php\n"
                    . "declare(strict_types=1);\n\n"
                    . "require __DIR__ . '/../vendor/autoload.php';\n\n"
                    . "Tester\\Environment::setup();\n";

                file_put_contents($bootstrapPath, $bootstrapContent);
                $io->comment('Created missing tests/bootstrap.php for Nette Tester.');
            }
        }

        $testNamespace = 'Tests\\Unit';
        $composerPath = $this->basePath . '/composer.json';

        if (file_exists($composerPath)) {
            $composerData = json_decode((string) file_get_contents($composerPath), true);
            $psr4 = isset($composerData['autoload-dev']['psr-4']) ? $composerData['autoload-dev']['psr-4'] : [];

            foreach ($psr4 as $namespace => $path) {
                $cleanPath = trim((string) $path, '/\\');
                if ($cleanPath === 'tests' || $cleanPath === 'tests/Unit') {
                    $testNamespace = rtrim((string) $namespace, '\\');
                    break;
                }
            }
        }

        $subPath = dirname($name);
        if ($subPath !== '.') {
            $testNamespace .= '\\' . str_replace('/', '\\', $subPath);
        }

        $extension = $isPhpUnit ? '.php' : '.phpt';
        $targetPath = $this->basePath . '/tests/Unit/' . $name . 'Test' . $extension;

        if (file_exists($targetPath)) {
            $io->error(sprintf('Test already exists at %s', $targetPath));
            return Command::FAILURE;
        }

        $depth = count(explode('/', $name)) + 1;
        $relativeBootstrap = str_repeat('../', $depth) . 'bootstrap.php';

        $generatedMethods = '';

        if (class_exists($testedClassFqn)) {
            try {
                $reflection = new \ReflectionClass($testedClassFqn);
                $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

                foreach ($methods as $method) {
                    if ($method->getDeclaringClass()->getName() !== $testedClassFqn) {
                        continue;
                    }

                    $methodName = $method->getName();

                    if ($methodName === '__construct' || strncmp($methodName, '__', 2) === 0) {
                        continue;
                    }

                    $testMethodName = 'test' . ucfirst($methodName);

                    if ($isPhpUnit) {
                        $generatedMethods .= "\n    public function " . $testMethodName . "(): void\n    {\n        \$this->markTestIncomplete('This test has not been implemented yet.');\n    }\n";
                    } else {
                        $generatedMethods .= "\n    public function " . $testMethodName . "(): void\n    {\n        // TODO: Test \$this->" . $testedClassVar . "->" . $methodName . "()\n        Assert::fail('Test not implemented.');\n    }\n";
                    }
                }
            } catch (\ReflectionException $e) {
            }
        }

        if ($generatedMethods === '') {
            if ($isPhpUnit) {
                $generatedMethods = "\n    public function testSuccess(): void\n    {\n        \$this->assertTrue(true);\n    }\n";
            } else {
                $generatedMethods = "\n    public function testSuccess(): void\n    {\n        Assert::true(true);\n    }\n";
            }
        }

        $stubFile = $isPhpUnit ? 'test.phpunit.stub' : 'test.tester.stub';
        $stubPath = dirname(__DIR__, 2) . '/stubs/' . $stubFile;

        if (!file_exists($stubPath)) {
            $io->error(sprintf('Template stub file not found at %s', $stubPath));
            return Command::FAILURE;
        }

        $stub = (string) file_get_contents($stubPath);

        $search = [
            '{{NAMESPACE}}',
            '{{CLASS_NAME}}',
            '{{TESTED_CLASS_FQN}}',
            '{{TESTED_CLASS_NAME}}',
            '{{TESTED_CLASS_VAR}}',
            '{{RELATIVE_BOOTSTRAP}}',
            '{{METHODS}}'
        ];

        $replace = [
            $testNamespace,
            $testClassName,
            $testedClassFqn,
            $className,
            $testedClassVar,
            $relativeBootstrap,
            $generatedMethods
        ];

        $content = str_replace($search, $replace, $stub);

        $dir = dirname($targetPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents($targetPath, $content);

        $io->writeln('<fg=green>✓ ' . ($isPhpUnit ? 'PHPUnit' : 'Nette Tester') . ' test created:</fg=green> tests/Unit/' . $name . 'Test' . ($isPhpUnit ? '.php' : '.phpt'));

        return Command::SUCCESS;
    }
}
