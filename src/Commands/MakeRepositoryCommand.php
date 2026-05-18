<?php

declare(strict_types=1);

namespace Unquam\NetteMaker\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Unquam\NetteMaker\Exceptions\GeneratorException;
use Unquam\NetteMaker\Generators\RepositoryGenerator;
use Unquam\NetteMaker\Support\Inflector;

class MakeRepositoryCommand extends Command
{
    protected static $defaultName = 'make:repository';
    protected static $defaultDescription = 'Create a new repository class';

    public function __construct()
    {
        parent::__construct('make:repository');
    }

    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'The name of the repository');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $name = Inflector::toClassName($input->getArgument('name'));
            $generator = new RepositoryGenerator(getcwd());
            $file = $generator->generate($name);

            $io->success('Repository created: ' . $file);

            return Command::SUCCESS;

        } catch (GeneratorException $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}