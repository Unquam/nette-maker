<?php

declare(strict_types=1);

namespace Unquam\NetteMaker\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Unquam\NetteMaker\Exceptions\GeneratorException;
use Unquam\NetteMaker\Generators\ModelGenerator;
use Unquam\NetteMaker\Support\Inflector;

class MakeModelCommand extends Command
{
    protected static $defaultName = 'make:model';
    protected static $defaultDescription = 'Create a new model class';

    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'The name of the model');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $name = Inflector::toClassName($input->getArgument('name'));
            $generator = new ModelGenerator(getcwd());
            $file = $generator->generate($name);

            $io->success('Model created: ' . $file);

            return Command::SUCCESS;

        } catch (GeneratorException $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}