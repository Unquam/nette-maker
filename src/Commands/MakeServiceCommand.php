<?php

declare(strict_types=1);

namespace Unquam\NetteMaker\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Unquam\NetteMaker\Exceptions\GeneratorException;
use Unquam\NetteMaker\Generators\ServiceGenerator;
use Unquam\NetteMaker\Support\Inflector;

class MakeServiceCommand extends Command
{
    protected static $defaultName = 'make:service';
    protected static $defaultDescription = 'Create a new service class';

    public function __construct()
    {
        parent::__construct('make:service');
    }

    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'The name of the service');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $name = Inflector::toClassName($input->getArgument('name'));
            $generator = new ServiceGenerator(getcwd());
            $file = $generator->generate($name);

            $io->writeln('<fg=green>✓ Service created:</fg=green> ' . basename(dirname($file)) . '/' . basename($file));

            return Command::SUCCESS;

        } catch (GeneratorException $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}