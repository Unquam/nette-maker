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
use Unquam\NetteMaker\Generators\SeederGenerator;

class MakeSeederCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'make:seeder';

    /** @var string */
    protected static $defaultDescription = 'Create a new database seeder class';

    /** @var string */
    private $configFile;

    public function __construct(string $configFile)
    {
        parent::__construct('make:seeder');
        $this->configFile = $configFile;
    }

    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'The name of the seeder');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $name = $input->getArgument('name');
        $basePath = dirname($this->configFile);

        try {
            $seedersDir = $this->resolveSeedersDir($basePath);
            $generator = new SeederGenerator($seedersDir);
            $file = $generator->generate($name);

            $io->writeln('<fg=green>✓ Seeder created:</fg=green> ' . basename(dirname($file)) . '/' . basename($file));
            return Command::SUCCESS;

        } catch (GeneratorException $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }

    private function resolveSeedersDir(string $basePath): string
    {
        $defaultDir = $basePath . '/db/seeders';

        if (!file_exists($this->configFile)) {
            return $defaultDir;
        }

        $config = Neon::decodeFile($this->configFile);
        if (isset($config['seeders']['directory'])) {
            return $basePath . '/' . ltrim((string) $config['seeders']['directory'], '/');
        }

        return $defaultDir;
    }
}