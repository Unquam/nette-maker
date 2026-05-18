<?php

declare(strict_types=1);

namespace Unquam\NetteMaker\Commands;

use Nette\Neon\Neon;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Unquam\NetteMaker\Exceptions\GeneratorException;
use Unquam\NetteMaker\Generators\FactoryGenerator;

class MakeFactoryCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'make:factory';

    /** @var string */
    protected static $defaultDescription = 'Create a new data factory blueprint';

    /** @var string */
    private $configFile;

    public function __construct(string $configFile)
    {
        parent::__construct('make:factory');
        $this->configFile = $configFile;
    }

    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::OPTIONAL, 'The name of the factory');
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        if ($input->getArgument('name') !== null && $input->getArgument('name') !== '') {
            return;
        }

        $helper = $this->getHelper('question');
        $question = new Question(
            '<fg=cyan>? Enter the name of the factory (e.g. UserFactory):</fg=cyan> '
        );

        $question->setValidator(function ($answer) {
            if ($answer === null || trim((string) $answer) === '') {
                throw new \RuntimeException('The factory name cannot be empty.');
            }
            return trim((string) $answer);
        });

        /** @var string $name */
        $name = $helper->ask($input, $output, $question);
        $input->setArgument('name', $name);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $rawName = $input->getArgument('name');

        if ($rawName === null || $rawName === '') {
            $io->error('The factory name is required.');
            return Command::FAILURE;
        }

        $basePath = dirname($this->configFile);

        try {
            $factoriesDir = $this->resolveFactoriesDir($basePath);
            $generator = new FactoryGenerator($factoriesDir);
            $file = $generator->generate($rawName);

            $io->writeln('<fg=green>✓ Factory created:</fg=green> ' . basename(dirname($file)) . '/' . basename($file));
            return Command::SUCCESS;

        } catch (GeneratorException $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }

    private function resolveFactoriesDir(string $basePath): string
    {
        $defaultDir = $basePath . '/db/factories';

        if (!file_exists($this->configFile)) {
            return $defaultDir;
        }

        $config = Neon::decodeFile($this->configFile);
        if (isset($config['factories']['directory'])) {
            return $basePath . '/' . ltrim((string) $config['factories']['directory'], '/');
        }

        return $defaultDir;
    }
}