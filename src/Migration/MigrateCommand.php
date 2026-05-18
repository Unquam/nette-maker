<?php

declare(strict_types=1);

namespace Unquam\NetteMaker\Migration;

use Nette\Neon\Neon;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Unquam\NetteMaker\Exceptions\MigrationException;

class MigrateCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'migrate';

    /** @var string */
    protected static $defaultDescription = 'Run or rollback database migrations';

    /** @var string */
    private $configPath;

    public function __construct(string $configPath)
    {
        // Explicitly pass command name to the parent constructor to prevent any Symfony Console errors
        parent::__construct('migrate');
        $this->configPath = $configPath;
    }

    protected function configure(): void
    {
        // Cleaned up: removed setDescription and setName to eliminate syntax duplication
        $this
            ->addOption('rollback', null, InputOption::VALUE_NONE, 'Rollback the last batch of migrations')
            ->addOption('status', null, InputOption::VALUE_NONE, 'Show the status of all migrations');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $runner = $this->createRunner();

            if ($input->getOption('status')) {
                return $this->showStatus($runner, $io);
            }

            if ($input->getOption('rollback')) {
                return $this->rollback($runner, $io);
            }

            return $this->migrate($runner, $io);

        } catch (MigrationException $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }

    private function migrate(MigrationRunner $runner, SymfonyStyle $io): int
    {
        $ran = $runner->run();

        if (empty($ran)) {
            $io->info('Nothing to migrate.');
            return Command::SUCCESS;
        }

        foreach ($ran as $migration) {
            $io->writeln('<info>Migrated:</info> ' . $migration);
        }

        $io->success('All migrations ran successfully!');
        return Command::SUCCESS;
    }

    private function rollback(MigrationRunner $runner, SymfonyStyle $io): int
    {
        $rolledBack = $runner->rollback();

        if (empty($rolledBack)) {
            $io->info('Nothing to rollback.');
            return Command::SUCCESS;
        }

        foreach ($rolledBack as $migration) {
            $io->writeln('<comment>Rolled back:</comment> ' . $migration);
        }

        $io->success('Rollback completed!');
        return Command::SUCCESS;
    }

    private function showStatus(MigrationRunner $runner, SymfonyStyle $io): int
    {
        $status = $runner->status();

        if (empty($status)) {
            $io->info('No migrations found.');
            return Command::SUCCESS;
        }

        $io->section('Migration Statuses');

        foreach ($status as $row) {
            $state = $row['ran']
                ? '<info>[Ran]</info>    '
                : '<comment>[Pending]</comment>';

            $io->writeln($state . ' ' . $row['migration']);
        }

        return Command::SUCCESS;
    }

    private function createRunner(): MigrationRunner
    {
        $config = $this->resolveConfig();

        $pdo = new \PDO(
            $config['dsn'],
            $config['user'] ?? null,
            $config['password'] ?? null,
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
        );

        $driver = DriverFactory::create($config['driver']);
        $migrationsPath = $config['migrations_dir'];

        return new MigrationRunner($pdo, $driver, $migrationsPath);
    }

    /**
     * Resolve database configuration from nette-maker.neon.
     *
     * @return array<string, mixed>
     * @throws MigrationException
     */
    private function resolveConfig(): array
    {
        if (!file_exists($this->configPath)) {
            throw new MigrationException('Config file not found: ' . $this->configPath);
        }

        /** @var array<string, mixed> $neon */
        $neon = Neon::decodeFile($this->configPath);

        $database = $neon['database'] ?? [];

        if (empty($database['dsn'])) {
            throw new MigrationException(
                'Missing "database.dsn" in config file: ' . $this->configPath
            );
        }

        $basePath = dirname($this->configPath);
        $migrationsDir = isset($neon['migrations']['directory'])
            ? $basePath . '/' . ltrim((string) $neon['migrations']['directory'], '/')
            : $basePath . '/db/migrations';

        return $this->normalizeConfig([
            'dsn'            => (string) $database['dsn'],
            'user'           => $database['user'] ?? null,
            'password'       => $database['password'] ?? null,
            'migrations_dir' => $migrationsDir,
        ]);
    }

    /**
     * Add 'driver' key by parsing it from the DSN string.
     *
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     * @throws MigrationException
     */
    private function normalizeConfig(array $config): array
    {
        if (empty($config['driver'])) {
            $dsn = (string) ($config['dsn'] ?? '');
            $colonPos = strpos($dsn, ':');

            if ($colonPos === false) {
                throw new MigrationException(
                    'Cannot determine database driver from DSN: ' . $dsn
                );
            }

            $config['driver'] = substr($dsn, 0, $colonPos);
        }

        if (empty($config['migrations_dir'])) {
            $config['migrations_dir'] = dirname($this->configPath) . '/db/migrations';
        }

        return $config;
    }
}