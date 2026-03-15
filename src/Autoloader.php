<?php
/**
 * @copyright Actra AG - https://www.actra.ch
 * @license   MIT
 */

declare(strict_types=1);

namespace actra\autoloader;

use Exception;
use LogicException;
use Throwable;

require_once __DIR__ . '/AutoloaderPath.php';
require_once __DIR__ . '/AutoloaderPathMode.php';

class Autoloader
{
    private static ?Autoloader $registeredInstance = null;
    private string $cacheFilePath;
    private array $cachedClasses;
    /** @var AutoloaderPath[] */
    private array $paths = [];
    private bool $cachedClassesChanged = false;

    public static function register(string $cacheFilePath = ''): Autoloader
    {
        if (!is_null(value: Autoloader::$registeredInstance)) {
            throw new LogicException(message: 'Autoloader is already registered.');
        }
        Autoloader::$registeredInstance = new Autoloader(cacheFilePath: $cacheFilePath);
        spl_autoload_register(callback: [
            Autoloader::$registeredInstance,
            'doAutoload'
        ]);

        return Autoloader::$registeredInstance;
    }

    public static function get(): Autoloader
    {
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
        file_put_contents(
            filename: $this->cacheFilePath,
            data: '<?php return ' . var_export(value: $this->cachedClasses, return: true) . ';',
            flags: LOCK_EX
        );
    }

    private function __construct(string $cacheFilePath = '')
    {
        $this->cacheFilePath = trim(string: $cacheFilePath);
        $this->checkIfCacheDirectoryExists(cacheFilePath: $cacheFilePath);
        $this->cachedClasses = $this->initCachedClasses(cacheFilePath: $cacheFilePath);
    }

    private function checkIfCacheDirectoryExists(string $cacheFilePath): void
    {
        if ($cacheFilePath === '') {
            return;
        }
        $dir = dirname(path: $cacheFilePath);
        if (!is_dir(filename: $dir)) {
            throw new Exception(message: 'Cache-Directory ' . $dir . ' does not exist');
        }
    }

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
            return is_array(value: $cached) ? $cached : [];
        } catch (Throwable) {
            return [];
        }
    }

    private function doAutoload(string $className): bool
    {
        $includePath = $this->getPathFromCache(className: $className);
        if (!is_null(value: $includePath)) {
            require_once $includePath;

            return true;
        }
        foreach ($this->paths as $autoloaderPath) {
            $path = $autoloaderPath->path;
            $mode = $autoloaderPath->autoloaderPathMode;
            $prefix = $autoloaderPath->prefix;
            if (!str_starts_with(
                haystack: $className,
                needle: $prefix
            )) {
                continue;
            }
            $relativeClass = substr(
                string: $className,
                offset: strlen(string: $prefix)
            );
            $phpFilePath = $path . str_replace(
                    search: $mode->getDelimiter(),
                    replace: DIRECTORY_SEPARATOR,
                    subject: $relativeClass
                );
            foreach ($autoloaderPath->fileSuffixList as $fileSuffix) {
                $includePath = $phpFilePath . $fileSuffix;
                if (file_exists(filename: $includePath)) {
                    $this->doInclude(
                        includePath: $includePath,
                        className: $className
                    );
                    return true;
                }
            }
        }
        return false;
    }

    private function getPathFromCache(string $className): ?string
    {
        if (
            !array_key_exists(
                key: $className,
                array: $this->cachedClasses
            )) {
            return null;
        }
        $classPath = $this->cachedClasses[$className];
        if (file_exists(filename: $classPath)) {
            return $classPath;
        }
        if (file_exists(filename: 'phar://' . $classPath)) {
            return 'phar://' . $classPath;
        }

        return null;
    }

    private function doInclude(
        string $includePath,
        string $className
    ): void {
        require_once $includePath;
        $this->cachedClasses[$className] = $includePath;
        $this->cachedClassesChanged = true;
    }
}