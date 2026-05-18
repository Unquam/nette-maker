<?php

declare(strict_types=1);

namespace Unquam\NetteMaker\Migration;

use Nette\Neon\Neon;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Unquam\NetteMaker\Exceptions\MigrationException;

class MigrateCommand extends Command
{
    protected static $defaultName = 'migrate';

    private string $configPath;

    /** @var array<string, mixed> */
    private array $config = [];

    public function __construct(string $configPath)
    {
        parent::__construct();
        $this->configPath = $configPath;
    }

    protected function configure(): void
    {
        $this
            ->setName('migrate')
            ->setDescription('Run or rollback database migrations')
            ->addOption('rollback', null, InputOption::VALUE_NONE, 'Rollback the last batch of migrations')
            ->addOption('status', null, InputOption::VALUE_NONE, 'Show the status of all migrations');
    }

    /**
     * @param array<string, mixed> $config
     */
    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $runner = $this->createRunner();

            if ($input->getOption('status')) {
                return $this->showStatus($runner, $output);
            }

            if ($input->getOption('rollback')) {
                return $this->rollback($runner, $output);
            }

            return $this->migrate($runner, $output);

        } catch (MigrationException $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }

    private function migrate(MigrationRunner $runner, OutputInterface $output): int
    {
        $ran = $runner->run();

        if (empty($ran)) {
            $output->writeln('<info>Nothing to migrate.</info>');
            return Command::SUCCESS;
        }

        foreach ($ran as $migration) {
            $output->writeln('<info>Migrated:</info> ' . $migration);
        }

        return Command::SUCCESS;
    }

    private function rollback(MigrationRunner $runner, OutputInterface $output): int
    {
        $rolledBack = $runner->rollback();

        if (empty($rolledBack)) {
            $output->writeln('<info>Nothing to rollback.</info>');
            return Command::SUCCESS;
        }

        foreach ($rolledBack as $migration) {
            $output->writeln('<comment>Rolled back:</comment> ' . $migration);
        }

        return Command::SUCCESS;
    }

    private function showStatus(MigrationRunner $runner, OutputInterface $output): int
    {
        $status = $runner->status();

        if (empty($status)) {
            $output->writeln('<info>No migrations found.</info>');
            return Command::SUCCESS;
        }

        foreach ($status as $row) {
            $state = $row['ran']
                ? '<info>Ran</info>    '
                : '<comment>Pending</comment>';

            $output->writeln($state . ' ' . $row['migration']);
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
     * Resolve database configuration from injected config array or from nette-maker.neon.
     *
     * @return array<string, mixed>
     * @throws MigrationException
     */
    private function resolveConfig(): array
    {
        // Config was injected directly (e.g. from DI container)
        if (!empty($this->config['dsn'])) {
            return $this->normalizeConfig($this->config);
        }

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
            // Parse driver prefix from DSN, e.g. "mysql:host=..."  → "mysql"
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
            $config['migrations_dir'] = getcwd() . '/db/migrations';
        }

        return $config;
    }
}