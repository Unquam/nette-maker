<?php

declare(strict_types=1);

namespace Unquam\NetteMaker\Commands;

use Nette\Neon\Exception;
use Nette\Neon\Neon;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Unquam\NetteMaker\Exceptions\MigrationException;
use Unquam\NetteMaker\Migration\DatabaseWiper;
use Unquam\NetteMaker\Migration\DriverFactory;
use Unquam\NetteMaker\Migration\MigrationRunner;
use Unquam\NetteMaker\Migration\SeederRunner;

class FreshCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'migrate:fresh';

    /** @var string */
    protected static $defaultDescription = 'Drop all tables and re-run all migrations';

    /** @var string */
    private $configFile;

    public function __construct(string $configFile)
    {
        parent::__construct('migrate:fresh');
        $this->configFile = $configFile;
    }

    protected function configure(): void
    {
        // Added the optional seeding flag option shortcut pattern
        $this->addOption(
            'seed',
            's',
            InputOption::VALUE_NONE,
            'Automatically run database seeders after fresh migration execution routine'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $basePath = dirname($this->configFile);

        try {
            $config = $this->resolveConfig($basePath);

            $pdo = new \PDO(
                $config['dsn'],
                $config['user'] ?? null,
                $config['password'] ?? null,
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );

            $driver = DriverFactory::create($config['driver']);

            $wiper = new DatabaseWiper($pdo, $driver);
            $droppedTables = $wiper->wipe();

            if (!empty($droppedTables)) {
                $io->writeln(sprintf('<fg=green>✓ Dropped %d tables successfully.</fg=green>', count($droppedTables)));
            } else {
                $io->writeln('<fg=yellow>⚠ Database was already empty.</fg=yellow>');
            }

            $migrationRunner = new MigrationRunner($pdo, $driver, $config['migrations_dir']);
            $ranMigrations = $migrationRunner->run();

            if (empty($ranMigrations)) {
                $io->writeln('<fg=yellow>⚠ No migrations found to execute.</fg=yellow>');
            } else {
                foreach ($ranMigrations as $migration) {
                    $io->writeln('<fg=green>✓ Migrated:</fg=green> db/migrations/' . $migration);
                }
            }

            if ($input->getOption('seed')) {
                $io->newLine();
                $seederRunner = new SeederRunner($pdo, $config['seeders_dir']);
                $executedSeeders = $seederRunner->seed();

                if (empty($executedSeeders)) {
                    $io->writeln('<fg=yellow>⚠ No seeders found or executed.</fg=yellow>');
                } else {
                    foreach ($executedSeeders as $seeder) {
                        $io->writeln('<fg=green>✓ Seeded:</fg=green> db/seeders/' . $seeder);
                    }
                }
            }

            $io->newLine();
            $io->writeln('<fg=green>✓ Database fresh reset completed successfully!</fg=green>');
            return Command::SUCCESS;

        } catch (\Throwable $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Resolve all consolidated configurations boundaries layouts securely
     *
     * @return array<string, mixed>
     * @throws MigrationException|Exception
     */
    private function resolveConfig(string $basePath): array
    {
        if (!file_exists($this->configFile)) {
            throw new MigrationException('Config file not found: ' . $this->configFile);
        }

        /** @var array<string, mixed> $neon */
        $neon = Neon::decodeFile($this->configFile);
        $database = $neon['database'] ?? [];

        if (empty($database['dsn'])) {
            throw new MigrationException('Missing "database.dsn" in config file: ' . $this->configFile);
        }

        $dsn = (string) $database['dsn'];
        $colonPos = strpos($dsn, ':');
        $driver = $colonPos !== false ? substr($dsn, 0, $colonPos) : '';

        $migrationsDir = isset($neon['migrations']['directory'])
            ? $basePath . '/' . ltrim((string) $neon['migrations']['directory'], '/')
            : $basePath . '/db/migrations';

        $seedersDir = isset($neon['seeders']['directory'])
            ? $basePath . '/' . ltrim((string) $neon['seeders']['directory'], '/')
            : $basePath . '/db/seeders';

        return [
            'dsn'            => $dsn,
            'driver'         => $driver,
            'user'           => $database['user'] ?? null,
            'password'       => $database['password'] ?? null,
            'migrations_dir' => $migrationsDir,
            'seeders_dir'    => $seedersDir,
        ];
    }
}