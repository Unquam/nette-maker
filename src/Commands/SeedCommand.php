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
use Unquam\NetteMaker\Migration\SeederRunner;

class SeedCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'db:seed';

    /** @var string */
    protected static $defaultDescription = 'Seed the database with records';

    /** @var string */
    private $configFile;

    public function __construct(string $configFile)
    {
        parent::__construct('db:seed');
        $this->configFile = $configFile;
    }

    protected function configure(): void
    {
        $this->addOption(
            'class',
            'c',
            InputOption::VALUE_REQUIRED,
            'The class name of the specific seeder to run'
        );
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

            $runner = new SeederRunner($pdo, $config['seeders_dir']);
            $classOption = $input->getOption('class');

            if ($classOption !== null && $classOption !== '') {
                $seederName = $runner->seedOne((string) $classOption);
                $io->writeln('<fg=green>✓ Seeded:</fg=green> ' . basename($config['seeders_dir']) . '/' . $seederName);

                $io->newLine();
                $io->writeln('<fg=green>✓ Targeted seeding completed successfully!</fg=green>');
                return Command::SUCCESS;
            }

            $executed = $runner->seed();

            if (empty($executed)) {
                $io->writeln('<fg=yellow>⚠ No seeders found or executed.</fg=yellow>');
                return Command::SUCCESS;
            }

            foreach ($executed as $seederName) {
                $io->writeln('<fg=green>✓ Seeded:</fg=green> ' . basename($config['seeders_dir']) . '/' . $seederName);
            }

            $io->newLine();
            $io->writeln('<fg=green>✓ Database seeding completed successfully!</fg=green>');
            return Command::SUCCESS;

        } catch (MigrationException $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Resolve database and seeders configuration blocks from the configuration file.
     *
     * @return array<string, mixed>
     * @throws MigrationException|Exception
     */
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

        $basePath = dirname($this->configFile);
        $seedersDir = isset($neon['seeders']['directory'])
            ? $basePath . '/' . ltrim((string) $neon['seeders']['directory'], '/')
            : $basePath . '/db/seeders';

        return [
            'dsn'         => (string) $database['dsn'],
            'user'        => $database['user'] ?? null,
            'password'    => $database['password'] ?? null,
            'seeders_dir' => $seedersDir,
        ];
    }
}