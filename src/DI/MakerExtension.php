<?php

declare(strict_types=1);

namespace Unquam\NetteMaker\DI;

use Nette\DI\CompilerExtension;
use Unquam\NetteMaker\Commands\ClearCacheCommand;
use Unquam\NetteMaker\Commands\FreshCommand;
use Unquam\NetteMaker\Commands\MakeInitCommand;
use Unquam\NetteMaker\Commands\MakeLatteCommand;
use Unquam\NetteMaker\Commands\MakeMigrationCommand;
use Unquam\NetteMaker\Commands\MakeModelCommand;
use Unquam\NetteMaker\Commands\MakeModuleCommand;
use Unquam\NetteMaker\Commands\MakePresenterCommand;
use Unquam\NetteMaker\Commands\MakeRepositoryCommand;
use Unquam\NetteMaker\Commands\MakeSeederCommand;
use Unquam\NetteMaker\Commands\MakeServiceCommand;
use Unquam\NetteMaker\Commands\SeedCommand;
use Unquam\NetteMaker\Commands\WipeCommand;
use Unquam\NetteMaker\Migration\MigrateCommand;

class MakerExtension extends CompilerExtension
{
    public function loadConfiguration(): void
    {
        $builder = $this->getContainerBuilder();

        $projectRoot = dirname($builder->parameters['appDir']);
        $configFile = $projectRoot . '/nette-maker.neon';

        $tags = [
            'kdyby.console.command' => true,
            'contributte.console.command' => true,
        ];

        $builder->addDefinition($this->prefix('makeInit'))
            ->setFactory(MakeInitCommand::class, [$projectRoot])
            ->setTags($tags);

        $builder->addDefinition($this->prefix('makePresenter'))
            ->setFactory(MakePresenterCommand::class)
            ->setTags($tags);

        $builder->addDefinition($this->prefix('makeModel'))
            ->setFactory(MakeModelCommand::class)
            ->setTags($tags);

        $builder->addDefinition($this->prefix('makeMigration'))
            ->setFactory(MakeMigrationCommand::class, [$configFile])
            ->setTags($tags);

        $builder->addDefinition($this->prefix('makeSeeder'))
            ->setFactory(MakeSeederCommand::class, [$configFile])
            ->setTags($tags);

        $builder->addDefinition($this->prefix('makeLatte'))
            ->setFactory(MakeLatteCommand::class)
            ->setTags($tags);

        $builder->addDefinition($this->prefix('makeRepository'))
            ->setFactory(MakeRepositoryCommand::class)
            ->setTags($tags);

        $builder->addDefinition($this->prefix('makeService'))
            ->setFactory(MakeServiceCommand::class)
            ->setTags($tags);

        $builder->addDefinition($this->prefix('makeModule'))
            ->setFactory(MakeModuleCommand::class, [$configFile])
            ->setTags($tags);

        $builder->addDefinition($this->prefix('migrate'))
            ->setFactory(MigrateCommand::class, [$configFile])
            ->setTags($tags);

        $builder->addDefinition($this->prefix('migrateFresh'))
            ->setFactory(FreshCommand::class, [$configFile])
            ->setTags($tags);

        $builder->addDefinition($this->prefix('dbSeed'))
            ->setFactory(SeedCommand::class, [$configFile])
            ->setTags($tags);

        $builder->addDefinition($this->prefix('dbWipe'))
            ->setFactory(WipeCommand::class, [$configFile])
            ->setTags($tags);

        $builder->addDefinition($this->prefix('clearCache'))
            ->setFactory(ClearCacheCommand::class, [$configFile])
            ->setTags($tags);
    }
}