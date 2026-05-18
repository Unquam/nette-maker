<?php

declare(strict_types=1);

namespace Unquam\NetteMaker\Commands;

use Nette\Neon\Neon;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Unquam\NetteMaker\Exceptions\GeneratorException;
use Unquam\NetteMaker\Generators\LatteGenerator;
use Unquam\NetteMaker\Generators\MigrationGenerator;
use Unquam\NetteMaker\Generators\ModelGenerator;
use Unquam\NetteMaker\Generators\PresenterGenerator;
use Unquam\NetteMaker\Generators\RepositoryGenerator;
use Unquam\NetteMaker\Generators\ServiceGenerator;
use Unquam\NetteMaker\Support\Inflector;

class MakeModuleCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'make:module';

    /** @var string */
    protected static $defaultDescription = 'Create a new module - presenter, model, repository, service, migration and latte template';

    /** @var string */
    private $configFile;

    public function __construct(string $configFile)
    {
        parent::__construct('make:module');
        $this->configFile = $configFile;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the module')
            ->addOption('no-presenter', null, InputOption::VALUE_NONE, 'Skip presenter generation')
            ->addOption('no-model', null, InputOption::VALUE_NONE, 'Skip model generation')
            ->addOption('no-migration', null, InputOption::VALUE_NONE, 'Skip migration generation')
            ->addOption('no-latte', null, InputOption::VALUE_NONE, 'Skip latte template generation')
            ->addOption('no-repository', null, InputOption::VALUE_NONE, 'Skip repository generation')
            ->addOption('no-service', null, InputOption::VALUE_NONE, 'Skip service generation')
            ->addOption('only', null, InputOption::VALUE_REQUIRED, 'Generate only specific parts - presenter, model, repository, service, migration, latte');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $rawName = $input->getArgument('name');
        $name = Inflector::toClassName($rawName);
        $basePath = dirname($this->configFile);
        $only = $input->getOption('only');

        /** @var array|null $parts */
        $parts = $only ? array_map('trim', explode(',', $only)) : null;
        $errors = [];

        // 1. Generate Presenter
        if ($this->shouldGenerate('presenter', $parts, $input)) {
            try {
                $file = (new PresenterGenerator($basePath))->generate($name);
                $io->writeln('<fg=green>✓ Presenter created:</fg=green> ' . basename(dirname($file)) . '/' . basename($file));
            } catch (GeneratorException $e) {
                $errors[] = $e->getMessage();
            }
        }

        // 2. Generate Model
        if ($this->shouldGenerate('model', $parts, $input)) {
            try {
                $file = (new ModelGenerator($basePath))->generate($name);
                $io->writeln('<fg=green>✓ Model created:</fg=green> ' . basename(dirname($file)) . '/' . basename($file));
            } catch (GeneratorException $e) {
                $errors[] = $e->getMessage();
            }
        }

        // 3. Generate Repository
        if ($this->shouldGenerate('repository', $parts, $input)) {
            try {
                $file = (new RepositoryGenerator($basePath))->generate($name);
                $io->writeln('<fg=green>✓ Repository created:</fg=green> ' . basename(dirname($file)) . '/' . basename($file));
            } catch (GeneratorException $e) {
                $errors[] = $e->getMessage();
            }
        }

        // 4. Generate Service
        if ($this->shouldGenerate('service', $parts, $input)) {
            try {
                $file = (new ServiceGenerator($basePath))->generate($name);
                $io->writeln('<fg=green>✓ Service created:</fg=green> ' . basename(dirname($file)) . '/' . basename($file));
            } catch (GeneratorException $e) {
                $errors[] = $e->getMessage();
            }
        }

        // 5. Generate Migration
        if ($this->shouldGenerate('migration', $parts, $input)) {
            try {
                $migrationsDir = $this->resolveMigrationsDir($basePath);
                $migrationName = 'create_' . Inflector::toTableName($rawName) . '_table';
                $file = (new MigrationGenerator($migrationsDir))->generate($migrationName);
                $io->writeln('<fg=green>✓ Migration created:</fg=green> ' . basename(dirname($file)) . '/' . basename($file));
            } catch (GeneratorException $e) {
                $errors[] = $e->getMessage();
            }
        }

        // 6. Generate Latte Template
        if ($this->shouldGenerate('latte', $parts, $input)) {
            try {
                $file = (new LatteGenerator($basePath))->generate($name);
                $io->writeln('<fg=green>✓ Latte template created:</fg=green> ' . basename(dirname($file)) . '/' . basename($file));
            } catch (GeneratorException $e) {
                $errors[] = $e->getMessage();
            }
        }

        // Output formatting structured into standard blocks if any execution boundary fails
        if (!empty($errors)) {
            $io->newLine();
            foreach ($errors as $error) {
                $io->error($error);
            }
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function resolveMigrationsDir(string $basePath): string
    {
        $defaultDir = $basePath . '/db/migrations';

        if (!file_exists($this->configFile)) {
            return $defaultDir;
        }

        $config = Neon::decodeFile($this->configFile);
        if (isset($config['migrations']['directory'])) {
            return $basePath . '/' . ltrim($config['migrations']['directory'], '/');
        }

        return $defaultDir;
    }

    private function shouldGenerate(string $part, ?array $only, InputInterface $input): bool
    {
        if ($only !== null) {
            return in_array($part, $only, true);
        }

        return !$input->getOption('no-' . $part);
    }
}