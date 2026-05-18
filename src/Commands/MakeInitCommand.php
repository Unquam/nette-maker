<?php

declare(strict_types=1);

namespace Unquam\NetteMaker\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Unquam\NetteMaker\Exceptions\GeneratorException;

class MakeInitCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'make:init';

    /** @var string */
    protected static $defaultDescription = 'Create default nette-maker.neon config file in project root';

    /** @var string */
    private $projectPath;

    public function __construct(string $projectPath)
    {
        parent::__construct('make:init');
        $this->projectPath = $projectPath;
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $target = $this->projectPath . '/nette-maker.neon';

            if (file_exists($target)) {
                $io->writeln('<fg=yellow>⚠ Config file already exists:</fg=yellow> nette-maker.neon');
                return Command::SUCCESS;
            }

            $stub = dirname(__DIR__, 2) . '/stubs/nette-maker.neon.stub';

            if (!file_exists($stub)) {
                throw new GeneratorException('Stub file not found: ' . $stub);
            }

            if (!copy($stub, $target)) {
                throw new GeneratorException('Failed to create config file: ' . $target);
            }

            $io->writeln('<fg=green>✓ Config file created:</fg=green> nette-maker.neon');
            $io->writeln('<comment>Please update your database credentials in nette-maker.neon</comment>');

            return Command::SUCCESS;

        } catch (GeneratorException $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}