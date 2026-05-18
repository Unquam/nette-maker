<?php

declare(strict_types=1);

namespace Unquam\NetteMaker\Commands;

use Nette\Neon\Neon;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Unquam\NetteMaker\Exceptions\GeneratorException;
use Unquam\NetteMaker\Generators\AuthGenerator;

class MakeAuthCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'make:auth';

    /** @var string */
    protected static $defaultDescription = 'Scaffold a complete Nette authentication system (migration, model, presenter, and views)';

    /** @var string */
    private $configFile;

    public function __construct(string $configFile)
    {
        parent::__construct('make:auth');
        $this->configFile = $configFile;
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $basePath = dirname($this->configFile);

        try {
            $migrationsDir = $this->resolveMigrationsDir($basePath);
            $generator = new AuthGenerator($basePath, $migrationsDir);

            $backendFiles = $generator->generateCore();

            $frontendFiles = $generator->generatePresentation();

            $allCreatedFiles = array_merge($backendFiles, $frontendFiles);

            if (empty($allCreatedFiles)) {
                $io->writeln('<fg=yellow>⚠ Authentication scaffolding already exists. No files were modified.</fg=yellow>');
                return Command::SUCCESS;
            }

            foreach ($allCreatedFiles as $file) {
                $relativePath = str_replace($basePath . '/', '', $file);
                $io->writeln('<fg=green>✓ Created:</fg=green> ' . $relativePath);
            }

            $io->newLine();
            $io->section('Next Steps - Security Configuration');
            $io->writeln('To activate your new authentication layers, open your <info>config.neon</info> and append the following definitions:');
            $io->newLine();
            $io->writeln('<comment>services:</comment>');
            $io->writeln('    <comment>- App\Model\Security\Authenticator</comment>');
            $io->newLine();
            $io->writeln('Then, run your newly scaffolded users database migration table script via:');
            $io->writeln('    <info>php nette migrate</info>');
            $io->newLine();

            return Command::SUCCESS;

        } catch (GeneratorException $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }

    private function resolveMigrationsDir(string $basePath): string
    {
        $defaultDir = $basePath . '/db/migrations';

        if (!file_exists($this->configFile)) {
            return $defaultDir;
        }

        $config = Neon::decodeFile($this->configFile);
        if (isset($config['migrations']['directory'])) {
            return $basePath . '/' . ltrim((string) $config['migrations']['directory'], '/');
        }

        return $defaultDir;
    }
}