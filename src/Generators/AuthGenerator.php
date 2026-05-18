<?php

declare(strict_types=1);

namespace Unquam\NetteMaker\Generators;

use Unquam\NetteMaker\Exceptions\GeneratorException;

class AuthGenerator
{
    /** @var string */
    private $basePath;

    /** @var string */
    private $migrationsDir;

    public function __construct(string $basePath, string $migrationsDir)
    {
        $this->basePath = rtrim($basePath, '/');
        $this->migrationsDir = rtrim($migrationsDir, '/');
    }

    /**
     * Generate baseline security core backend components.
     *
     * @return array<string> List of successfully created file absolute paths
     * @throws GeneratorException
     */
    public function generateCore(): array
    {
        $createdFiles = [];

        $securityDir = $this->basePath . '/app/Model/Security';
        if (!is_dir($securityDir) && !@mkdir($securityDir, 0755, true) && !is_dir($securityDir)) {
            throw new GeneratorException('Failed to create directory: ' . $securityDir);
        }

        $authenticatorFile = $securityDir . '/Authenticator.php';
        if (!file_exists($authenticatorFile)) {
            $stubAuth = dirname(__DIR__, 2) . '/stubs/auth/Authenticator.stub';
            if (!file_exists($stubAuth)) {
                throw new GeneratorException('Authenticator stub file not found.');
            }
            copy($stubAuth, $authenticatorFile);
            $createdFiles[] = $authenticatorFile;
        }

        if (!is_dir($this->migrationsDir) && !@mkdir($this->migrationsDir, 0755, true) && !is_dir($this->migrationsDir)) {
            throw new GeneratorException('Failed to create directory: ' . $this->migrationsDir);
        }

        $existingMigrations = glob($this->migrationsDir . '/*_create_users_table.php');
        if (empty($existingMigrations)) {
            $migrationName = date('Y_m_d_His') . '_create_users_table.php';
            $migrationFile = $this->migrationsDir . '/' . $migrationName;

            $stubMigration = dirname(__DIR__, 2) . '/stubs/auth/migration.stub';
            if (!file_exists($stubMigration)) {
                throw new GeneratorException('Auth migration stub file not found.');
            }
            copy($stubMigration, $migrationFile);
            $createdFiles[] = $migrationFile;
        }

        return $createdFiles;
    }

    /**
     * Generate presentation frontend components layers for authentication handling.
     *
     * @return array<string> List of successfully created presentation files
     * @throws GeneratorException
     */
    public function generatePresentation(): array
    {
        $createdFiles = [];
        $presentationDir = $this->basePath . '/app/Presentation/Sign';

        if (!is_dir($presentationDir) && !@mkdir($presentationDir, 0755, true) && !is_dir($presentationDir)) {
            throw new GeneratorException('Failed to create directory: ' . $presentationDir);
        }

        $presenterFile = $presentationDir . '/SignPresenter.php';
        if (!file_exists($presenterFile)) {
            $stubPresenter = dirname(__DIR__, 2) . '/stubs/auth/SignPresenter.stub';
            if (!file_exists($stubPresenter)) {
                throw new GeneratorException('SignPresenter stub file not found.');
            }
            copy($stubPresenter, $presenterFile);
            $createdFiles[] = $presenterFile;
        }

        $inFile = $presentationDir . '/in.latte';
        if (!file_exists($inFile)) {
            $stubIn = dirname(__DIR__, 2) . '/stubs/auth/in.latte';
            if (!file_exists($stubIn)) {
                throw new GeneratorException('in.latte stub file not found.');
            }
            copy($stubIn, $inFile);
            $createdFiles[] = $inFile;
        }

        $upFile = $presentationDir . '/up.latte';
        if (!file_exists($upFile)) {
            $stubUp = dirname(__DIR__, 2) . '/stubs/auth/up.latte';
            if (!file_exists($stubUp)) {
                throw new GeneratorException('up.latte stub file not found.');
            }
            copy($stubUp, $upFile);
            $createdFiles[] = $upFile;
        }

        return $createdFiles;
    }
}