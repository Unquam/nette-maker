<?php

declare(strict_types=1);

namespace Unquam\NetteMaker\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Unquam\NetteMaker\Exceptions\GeneratorException;
use Unquam\NetteMaker\Generators\RequestGenerator;

class MakeRequestCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'make:request';

    /** @var string */
    protected static $defaultDescription = 'Create a new Form Request validation class inside modular Feature Folders';

    /** @var string */
    private $configFile;

    public function __construct(string $configFile)
    {
        parent::__construct('make:request');
        $this->configFile = $configFile;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::OPTIONAL, 'The modular name of the request class (e.g. Article/Store)')
            ->addOption('web', null, InputOption::VALUE_NONE, 'Generate a request class for the Web Frontend requests namespace');
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        if ($input->getArgument('name') !== null && $input->getArgument('name') !== '') {
            return;
        }

        $helper = $this->getHelper('question');
        $question = new \Symfony\Component\Console\Question\Question(
            '<fg=cyan>? Enter the modular name of the request (e.g. Article/Store):</fg=cyan> '
        );

        $question->setValidator(function ($answer) {
            if ($answer === null || trim((string) $answer) === '' || strpos((string) $answer, '/') === false) {
                throw new \RuntimeException('The request name must include a module separator format (e.g. Article/Store).');
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

        if ($rawName === null || $rawName === '' || strpos($rawName, '/') === false) {
            $io->error('The request name must match the "Module/RequestName" modular pattern.');
            return Command::FAILURE;
        }

        // Split the modular input pattern string cleanly into Module and Request components
        [$module, $requestName] = explode('/', $rawName, 2);

        $basePath = dirname($this->configFile);
        $isWeb = (bool) $input->getOption('web');

        try {
            $generator = new RequestGenerator($basePath);
            $file = $generator->generate($module, $requestName, $isWeb);

            // Clean display path for elite UI logs output mapping
            $displayPath = str_replace($basePath . '/app/Presentation/', '', $file);
            $io->writeln('<fg=green>✓ Request created:</fg=green> Presentation/' . $displayPath);
            return Command::SUCCESS;

        } catch (GeneratorException $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}