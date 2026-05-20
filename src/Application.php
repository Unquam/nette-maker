<?php

declare(strict_types=1);

namespace Unquam\NetteMaker;

use Symfony\Component\Console\Application as ConsoleApplication;
use Unquam\NetteMaker\Commands\ClearCacheCommand;
use Unquam\NetteMaker\Commands\FreshCommand;
use Unquam\NetteMaker\Commands\MakeFactoryCommand;
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

class Application extends ConsoleApplication
{
    /** @var string */
    private const VERSION = '2.2.2';

    /** @var string */
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
            new FreshCommand($configFile),

            // Database seeders and data factories layer management commands
            new MakeFactoryCommand($configFile),
            new MakeSeederCommand($configFile),
            new SeedCommand($configFile),
            new WipeCommand($configFile),

            // Complete module scaffold generator command
            new MakeModuleCommand($configFile),

            // Individual single file code generators commands
            new MakePresenterCommand(),
            new MakeModelCommand(),
            new MakeLatteCommand(),
            new MakeRepositoryCommand(),
            new MakeServiceCommand(),

            // Inside registerCommands() method array stack:
            new Commands\MakeAuthCommand($configFile),

            new Commands\MakeResourceCommand($configFile),

            new Commands\MakeRequestCommand($configFile),
        ]);
    }
}