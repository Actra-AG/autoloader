<?php

/**
 * @copyright Actra AG - https://www.actra.ch
 * @license   MIT
 */

declare(strict_types=1);

namespace actra\autoloader;

use LogicException;
use RuntimeException;
use Throwable;

require_once __DIR__ . '/AutoloaderPath.php';
require_once __DIR__ . '/AutoloaderPathMode.php';
require_once __DIR__ . '/CacheFileWriter.php';

final class Autoloader
{
    private static ?Autoloader $registeredInstance = null;
    private string $cacheFilePath;
    /** @var array<string, string> */
    private array $cachedClasses;
    /** @var AutoloaderPath[] */
    private array $paths = [];
    private bool $cachedClassesChanged = false;

    public static function register(string $cacheFilePath = ''): Autoloader
    {
        if (Autoloader::$registeredInstance !== null) {
            throw new LogicException(message: 'Autoloader is already registered.');
        }
        Autoloader::$registeredInstance = new Autoloader(cacheFilePath: $cacheFilePath);
        spl_autoload_register(callback: [
            Autoloader::$registeredInstance,
            'doAutoload',
        ]);

        return Autoloader::$registeredInstance;
    }

    public static function get(): Autoloader
    {
        if (Autoloader::$registeredInstance === null) {
            throw new LogicException(message: 'Autoloader is not registered, call Autoloader::register() first.');
        }

        return Autoloader::$registeredInstance;
    }

    public function addPath(AutoloaderPath $autoloaderPath): void
    {
        $this->paths[] = $autoloaderPath;
    }

    public function __destruct()
    {
        if (
            $this->cacheFilePath === ''
            || !$this->cachedClassesChanged
        ) {
            return;
        }
        $written = new CacheFileWriter()->write(
            cacheFilePath: $this->cacheFilePath,
            cachedClasses: $this->cachedClasses,
        );
        if ($written) {
            $this->cachedClassesChanged = false;
        }
    }

    private function __construct(string $cacheFilePath = '')
    {
        $this->cacheFilePath = trim(string: $cacheFilePath);
        if ($this->cacheFilePath === '') {
            $this->cacheFilePath = __DIR__ . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'autoloader.php';
        }
        $this->ensureCacheDirectoryExists(cacheFilePath: $this->cacheFilePath);
        $this->cachedClasses = $this->initCachedClasses(cacheFilePath: $this->cacheFilePath);
    }

    private function ensureCacheDirectoryExists(string $cacheFilePath): void
    {
        $dir = dirname(path: $cacheFilePath);
        if (is_dir(filename: $dir)) {
            return;
        }
        if (
            !mkdir(
                directory: $dir,
                recursive: true,
            )
            && !is_dir(filename: $dir)
        ) {
            throw new RuntimeException(message: 'Cache-Directory ' . $dir . ' could not be created');
        }
    }

    /**
     * @return array<string, string>
     */
    private function initCachedClasses(string $cacheFilePath): array
    {
        if (
            $cacheFilePath === ''
            || !file_exists(filename: $cacheFilePath)
        ) {
            return [];
        }
        try {
            $cached = include $cacheFilePath;
            if (!is_array(value: $cached)) {
                return [];
            }

            // Written by CacheFileWriter only, so the content is not checked again for every request
            /** @var array<string, string> $cached */
            return $cached;
        } catch (Throwable) {
            return [];
        }
    }

    private function doAutoload(string $className): bool
    {
        if (array_key_exists(key: $className, array: $this->cachedClasses)) {
            if ($this->includeCachedFile(includePath: $this->cachedClasses[$className])) {
                return true;
            }
            // Stale entry: remove it, even if the class is not found anymore
            unset($this->cachedClasses[$className]);
            $this->cachedClassesChanged = true;
        }
        foreach ($this->paths as $autoloaderPath) {
            $path = $autoloaderPath->path;
            $mode = $autoloaderPath->autoloaderPathMode;
            $prefix = $autoloaderPath->prefix;
            if (!str_starts_with(
                haystack: $className,
                needle: $prefix,
            )) {
                continue;
            }
            $relativeClass = substr(
                string: $className,
                offset: strlen(string: $prefix),
            );
            $phpFilePath = $path . str_replace(
                search: $mode->getDelimiter(),
                replace: DIRECTORY_SEPARATOR,
                subject: $relativeClass,
            );
            foreach ($autoloaderPath->fileSuffixList as $fileSuffix) {
                $includePath = $phpFilePath . $fileSuffix;
                if (file_exists(filename: $includePath)) {
                    $this->doInclude(
                        includePath: $includePath,
                        className: $className,
                    );
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Includes the cached file without checking its existence first (saves a stat call per class), and reports
     * a missing file (moved or deleted since it was cached) as false instead of a warning.
     */
    private function includeCachedFile(string $includePath): bool
    {
        $previousErrorHandler = null;
        $previousErrorHandler = set_error_handler(
            callback: static function (
                int $errno,
                string $errstr,
                string $errfile,
                int $errline,
            ) use (&$previousErrorHandler): bool {
                // Warnings of the include statement itself; errors in the included file are passed on
                if ($errfile === __FILE__) {
                    return true;
                }
                if ($previousErrorHandler === null) {
                    return false;
                }

                return $previousErrorHandler($errno, $errstr, $errfile, $errline) !== false;
            },
        );
        try {
            return (include_once $includePath) !== false;
        } finally {
            restore_error_handler();
        }
    }

    private function doInclude(
        string $includePath,
        string $className,
    ): void {
        require_once $includePath;
        $this->cachedClasses[$className] = $includePath;
        $this->cachedClassesChanged = true;
    }
}
