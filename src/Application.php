<?php

declare(strict_types=1);

namespace Unquam\NetteMaker;

use Symfony\Component\Console\Application as ConsoleApplication;
use Unquam\NetteMaker\Commands\ClearCacheCommand;
use Unquam\NetteMaker\Commands\MakeInitCommand;
use Unquam\NetteMaker\Commands\MakeLatteCommand;
use Unquam\NetteMaker\Commands\MakeMigrationCommand;
use Unquam\NetteMaker\Commands\MakeModelCommand;
use Unquam\NetteMaker\Commands\MakeModuleCommand;
use Unquam\NetteMaker\Commands\MakePresenterCommand;
use Unquam\NetteMaker\Commands\MakeRepositoryCommand;
use Unquam\NetteMaker\Commands\MakeServiceCommand;
use Unquam\NetteMaker\Migration\MigrateCommand;

class Application extends ConsoleApplication
{
    private const VERSION = '1.3.0';
    private const NAME = 'Nette Maker';

    public function __construct(string $configFile)
    {
        parent::__construct(self::NAME, self::VERSION);

        $absoluteConfigPath = (string) realpath($configFile);
        if ($absoluteConfigPath === '') {
            $absoluteConfigPath = getcwd() . '/' . ltrim($configFile, './');
        }

        $this->registerCommands($absoluteConfigPath);
    }

    private function registerCommands(string $configFile): void
    {
        $projectPath = dirname($configFile);

        $this->addCommands([
            // Core package initialization and maintenance commands
            new MakeInitCommand($projectPath),
            new ClearCacheCommand($configFile),

            // Database migrations layer management commands
            new MakeMigrationCommand($configFile),
            new MigrateCommand($configFile),

            // Complete module scaffold generator command
            new MakeModuleCommand($configFile),

            // Individual single file code generators commands
            new MakePresenterCommand(),
            new MakeModelCommand(),
            new MakeLatteCommand(),
            new MakeRepositoryCommand(),
            new MakeServiceCommand(),

            // Inside registerCommands() method array:
            new Commands\MakeSeederCommand($configFile),
            new Commands\SeedCommand($configFile),
        ]);
    }
}
