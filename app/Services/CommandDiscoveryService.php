<?php

declare(strict_types=1);

namespace DeployerPHP\Services;

use DeployerPHP\Contracts\BaseCommand;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Discovers command classes by scanning the Console directory.
 */
class CommandDiscoveryService
{
    private const CONSOLE_PATH = 'app/Console';

    private const NAMESPACE_PREFIX = 'DeployerPHP\\Console\\';

    private readonly string $resolvedBasePath;

    public function __construct(
        private readonly string $basePath = '',
    ) {
        $this->resolvedBasePath = '' === $this->basePath
            ? dirname(__DIR__, 2)
            : $this->basePath;
    }

    /**
     * Discover all valid command class names.
     *
     * @return array<class-string<BaseCommand>>
     */
    public function discover(): array
    {
        $consolePath = $this->resolvedBasePath . '/' . self::CONSOLE_PATH;

        if (!is_dir($consolePath)) {
            return [];
        }

        $commands = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($consolePath)
        );

        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            if (!str_ends_with($file->getFilename(), 'Command.php')) {
                continue;
            }

            $realPath = $file->getRealPath();

            if (false === $realPath) {
                continue;
            }

            $className = $this->filePathToClassName($realPath, $consolePath);

            if (null === $className) {
                continue;
            }

            if (!$this->isValidCommandClass($className)) {
                continue;
            }

            /** @var class-string<BaseCommand> $className */
            $commands[] = $className;
        }

        sort($commands);

        return $commands;
    }

    /**
     * Convert file path to fully-qualified class name.
     */
    private function filePathToClassName(string $filePath, string $consolePath): ?string
    {
        $consolePath = rtrim($consolePath, '/') . '/';

        if (!str_starts_with($filePath, $consolePath)) {
            return null;
        }

        $relativePath = substr($filePath, strlen($consolePath), -4);
        $namespaceSuffix = str_replace('/', '\\', $relativePath);

        return self::NAMESPACE_PREFIX . $namespaceSuffix;
    }

    /**
     * Validate that a class is a registrable command.
     */
    private function isValidCommandClass(string $className): bool
    {
        if (!class_exists($className)) {
            return false;
        }

        $reflection = new ReflectionClass($className);

        if ($reflection->isAbstract()) {
            return false;
        }

        if (!$reflection->isSubclassOf(BaseCommand::class)) {
            return false;
        }

        $attributes = $reflection->getAttributes(AsCommand::class);

        return 0 !== count($attributes);
    }
}
