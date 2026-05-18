<?php

declare(strict_types=1);

namespace Unquam\NetteMaker\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Unquam\NetteMaker\Exceptions\GeneratorException;
use Unquam\NetteMaker\Generators\ResourceGenerator;

class MakeResourceCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'make:resource';

    /** @var string */
    protected static $defaultDescription = 'Create a new API json data transformer resource';

    /** @var string */
    private $configFile;

    public function __construct(string $configFile)
    {
        parent::__construct('make:resource');
        $this->configFile = $configFile;
    }

    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::OPTIONAL, 'The name of the resource');
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        if ($input->getArgument('name') !== null && $input->getArgument('name') !== '') {
            return;
        }

        $helper = $this->getHelper('question');
        $question = new \Symfony\Component\Console\Question\Question(
            '<fg=cyan>? Enter the name of the resource (e.g. UserResource or UserCollection):</fg=cyan> '
        );

        $question->setValidator(function ($answer) {
            if ($answer === null || trim((string) $answer) === '') {
                throw new \RuntimeException('The resource name cannot be empty.');
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
            $io->error('The resource name is required.');
            return Command::FAILURE;
        }

        $basePath = dirname($this->configFile);

        try {
            $generator = new ResourceGenerator($basePath);
            $file = $generator->generate($rawName);

            $io->writeln('<fg=green>✓ Resource created:</fg=green> ' . basename(dirname($file)) . '/' . basename($file));
            return Command::SUCCESS;

        } catch (GeneratorException $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}