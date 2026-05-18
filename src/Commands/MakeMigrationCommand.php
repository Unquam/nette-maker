<?php

declare(strict_types=1);

namespace Unquam\NetteMaker\Commands;

use Nette\Neon\Neon;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Unquam\NetteMaker\Exceptions\GeneratorException;
use Unquam\NetteMaker\Generators\MigrationGenerator;
use Unquam\NetteMaker\Support\Inflector;

class MakeMigrationCommand extends Command
{
    protected static $defaultName = 'make:migration';
    protected static $defaultDescription = 'Create a new migration file';

    /** @var string */
    private $configFile;

    public function __construct(string $configFile)
    {
        parent::__construct('make:migration');
        $this->configFile = $configFile;
    }

    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'The name of the migration');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $name = Inflector::toSnakeCase($input->getArgument('name'));
            $migrationsDir = $this->resolveMigrationsDir();
            $generator = new MigrationGenerator($migrationsDir);
            $file = $generator->generate($name);

            $io->writeln('<fg=green>✓ Migration created:</fg=green> ' . basename(dirname($file)) . '/' . basename($file));

            return Command::SUCCESS;

        } catch (GeneratorException $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }

    private function resolveMigrationsDir(): string
    {
        $basePath = dirname($this->configFile);
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
}