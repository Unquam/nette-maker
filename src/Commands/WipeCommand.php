<?php

declare(strict_types=1);

namespace Unquam\NetteMaker\Commands;

use Nette\Neon\Neon;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Unquam\NetteMaker\Exceptions\MigrationException;
use Unquam\NetteMaker\Migration\DatabaseWiper;
use Unquam\NetteMaker\Migration\DriverFactory;

class WipeCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'db:wipe';

    /** @var string */
    protected static $defaultDescription = 'Drop all tables from the database';

    /** @var string */
    private $configFile;

    public function __construct(string $configFile)
    {
        parent::__construct('db:wipe');
        $this->configFile = $configFile;
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $config = $this->resolveConfig();

            $pdo = new \PDO(
                $config['dsn'],
                $config['user'] ?? null,
                $config['password'] ?? null,
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );

            $driver = DriverFactory::create($config['driver']);
            $wiper = new DatabaseWiper($pdo, $driver);

            $droppedTables = $wiper->wipe();

            if (empty($droppedTables)) {
                $io->writeln('<fg=yellow>⚠ No tables found in the database schema.</fg=yellow>');
                return Command::SUCCESS;
            }

            foreach ($droppedTables as $table) {
                $io->writeln('<fg=green>✓ Dropped table:</fg=green> ' . $table);
            }

            $io->newLine();
            $io->writeln('<fg=green>✓ Database wipe completed successfully!</fg=green>');
            return Command::SUCCESS;

        } catch (\Throwable $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }

    private function resolveConfig(): array
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

        return [
            'dsn'      => $dsn,
            'driver'   => $driver,
            'user'     => $database['user'] ?? null,
            'password' => $database['password'] ?? null,
        ];
    }
}