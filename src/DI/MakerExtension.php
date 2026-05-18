<?php

declare(strict_types=1);

namespace Unquam\NetteMaker\DI;

use Nette\DI\CompilerExtension;
use Unquam\NetteMaker\Application;
use Unquam\NetteMaker\Commands\MakeInitCommand;
use Unquam\NetteMaker\Commands\MakeLatteCommand;
use Unquam\NetteMaker\Commands\MakeMigrationCommand;
use Unquam\NetteMaker\Commands\MakeModelCommand;
use Unquam\NetteMaker\Commands\MakeModuleCommand;
use Unquam\NetteMaker\Commands\MakePresenterCommand;
use Unquam\NetteMaker\Commands\MakeRepositoryCommand;
use Unquam\NetteMaker\Commands\MakeServiceCommand;
use Unquam\NetteMaker\Migration\MigrateCommand;

class MakerExtension extends CompilerExtension
{
    public function loadConfiguration(): void
    {
        $builder = $this->getContainerBuilder();
        $configFile = getcwd() . '/nette-maker.neon';

        $builder->addDefinition($this->prefix('application'))
            ->setFactory(Application::class, [$configFile]);

        $builder->addDefinition($this->prefix('makeInit'))
            ->setFactory(MakeInitCommand::class, [getcwd()]);

        $builder->addDefinition($this->prefix('makePresenter'))
            ->setFactory(MakePresenterCommand::class);

        $builder->addDefinition($this->prefix('makeModel'))
            ->setFactory(MakeModelCommand::class);

        $builder->addDefinition($this->prefix('makeMigration'))
            ->setFactory(MakeMigrationCommand::class, [$configFile]);

        $builder->addDefinition($this->prefix('makeLatte'))
            ->setFactory(MakeLatteCommand::class);

        $builder->addDefinition($this->prefix('makeRepository'))
            ->setFactory(MakeRepositoryCommand::class);

        $builder->addDefinition($this->prefix('makeService'))
            ->setFactory(MakeServiceCommand::class);

        $builder->addDefinition($this->prefix('makeModule'))
            ->setFactory(MakeModuleCommand::class, [$configFile]);

        $builder->addDefinition($this->prefix('migrate'))
            ->setFactory(MigrateCommand::class, [$configFile]);
    }
}