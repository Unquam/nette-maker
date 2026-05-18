<?php

declare(strict_types=1);

namespace Unquam\NetteMaker\Commands;

use Nette\Neon\Exception;
use Nette\Neon\Neon;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ClearCacheCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'clear:cache';

    /** @var string */
    protected static $defaultDescription = 'Safely clear application cache directories';

    /** @var string */
    private $configFile;

    public function __construct(string $configFile)
    {
        parent::__construct('clear:cache');
        $this->configFile = $configFile;
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $basePath = dirname($this->configFile);

        try {
            $directories = $this->resolveDirectories($basePath);

            foreach ($directories as $dirPath) {
                // Get clean relative path for beautiful console output formatting
                $relativeDisplayPath = str_replace($basePath . '/', '', $dirPath);

                if (!is_dir($dirPath)) {
                    $io->writeln('<fg=yellow>⚠ Directory does not exist:</fg=yellow> ' . $relativeDisplayPath);
                    continue;
                }

                // If it is the standard Nette framework temp directory, clear it safely (cache & proxies only)
                if (basename(rtrim($dirPath, '/')) === 'temp') {
                    $this->clearNetteTemp($dirPath, $io, $basePath);
                    continue;
                }

                // For any other custom directories (e.g. log, assets/cache), clear contents entirely
                $this->deleteDirectoryContents($dirPath);
                $io->writeln('<fg=green>✓ Cleared:</fg=green> ' . $relativeDisplayPath);
            }

            $io->newLine();
            $io->writeln('<fg=green>✓ Cache clearing process completed!</fg=green>');
            return Command::SUCCESS;

        } catch (\Throwable $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Safely clear Nette core temp directory without dropping live active user sessions
     */
    private function clearNetteTemp(string $tempDir, SymfonyStyle $io, string $basePath): void
    {
        $subDirs = ['cache', 'proxies'];

        foreach ($subDirs as $subDir) {
            $target = $tempDir . '/' . $subDir;
            $relativeSubPath = str_replace($basePath . '/', '', $target);

            if (is_dir($target)) {
                $this->deleteDirectoryContents($target);
                // Recreate target directory with secure permissions to prevent Nette runtime writing faults
                @mkdir($target, 0755, true);
                $io->writeln('<fg=green>✓ Cleared:</fg=green> ' . $relativeSubPath);
            }
        }
    }

    /**
     * Resolve target cache directory paths array from the active configurations layout
     *
     * @return array<string>
     * @throws Exception
     */
    private function resolveDirectories(string $basePath): array
    {
        $default = [$basePath . '/temp'];

        if (!file_exists($this->configFile)) {
            return $default;
        }

        $config = Neon::decodeFile($this->configFile);

        if (isset($config['cache']['directories']) && is_array($config['cache']['directories'])) {
            $dirs = [];
            foreach ($config['cache']['directories'] as $dir) {
                $dirs[] = $basePath . '/' . ltrim((string) $dir, '/');
            }
            return $dirs;
        }

        return $default;
    }

    /**
     * Recursive helper flushing directory items contents while maintaining the root folder wrapper intact
     */
    private function deleteDirectoryContents(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->deleteDirectoryContents($path);
                @rmdir($path);
            } else {
                @unlink($path);
            }
        }
    }
}