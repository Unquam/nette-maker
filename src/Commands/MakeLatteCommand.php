<?php

declare(strict_types=1);

namespace Unquam\NetteMaker\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Unquam\NetteMaker\Exceptions\GeneratorException;
use Unquam\NetteMaker\Generators\LatteGenerator;
use Unquam\NetteMaker\Support\Inflector;

class MakeLatteCommand extends Command
{
    protected static $defaultName = 'make:latte';
    protected static $defaultDescription = 'Create a new Latte template';

    public function __construct()
    {
        parent::__construct('make:latte');
    }

    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'The name of the template');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $name = Inflector::toClassName($input->getArgument('name'));
            $generator = new LatteGenerator(getcwd());
            $file = $generator->generate($name);

            $io->success('Latte template created: ' . $file);

            return Command::SUCCESS;

        } catch (GeneratorException $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}