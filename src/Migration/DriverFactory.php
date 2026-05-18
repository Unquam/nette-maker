<?php

declare(strict_types=1);

namespace Unquam\NetteMaker\Migration;

use Unquam\NetteMaker\Exceptions\MigrationException;
use Unquam\NetteMaker\Migration\Drivers\DriverInterface;
use Unquam\NetteMaker\Migration\Drivers\MySQLDriver;
use Unquam\NetteMaker\Migration\Drivers\PostgresDriver;
use Unquam\NetteMaker\Migration\Drivers\SQLiteDriver;
use Unquam\NetteMaker\Migration\Drivers\MSSQLDriver;

class DriverFactory
{
    private const DRIVERS = [
        'mysql'    => MySQLDriver::class,
        'mariadb'  => MySQLDriver::class,
        'pgsql'    => PostgresDriver::class,
        'postgres' => PostgresDriver::class,
        'sqlite'   => SQLiteDriver::class,
        'sqlsrv'   => MSSQLDriver::class,
        'mssql'    => MSSQLDriver::class,
    ];

    public static function create(string $driver): DriverInterface
    {
        $driver = strtolower($driver);

        if (!isset(self::DRIVERS[$driver])) {
            throw new MigrationException(
                'Unsupported database driver: "' . $driver . '". Supported drivers: ' . implode(', ', array_keys(self::DRIVERS))
            );
        }

        $class = self::DRIVERS[$driver];
        return new $class();
    }
}