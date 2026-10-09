<?php

/**
 * @copyright Actra AG - https://www.actra.ch
 * @license   MIT
 */

declare(strict_types=1);

namespace actra\autoloader\tests\Integration;

use actra\autoloader\Autoloader;
use actra\autoloader\AutoloaderPath;
use actra\autoloader\tests\Double\InMemoryStreamWrapper;
use Override;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Each test runs in its own process, because the autoloader is registered once per process.
 */
final class AutoloaderTest extends TestCase
{
    private TemporaryDirectory $temporaryDirectory;
    private string $cacheFilePath;

    #[Override]
    protected function setUp(): void
    {
        $this->temporaryDirectory = new TemporaryDirectory();
        $this->cacheFilePath = $this->temporaryDirectory->path . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'autoloader.php';
    }

    #[Override]
    protected function tearDown(): void
    {
        $this->temporaryDirectory->remove();
    }

    #[RunInSeparateProcess]
    public function testCachedClassIsLoadedFromCachedPathWithoutScanning(): void
    {
        $classFilePath = $this->writeClassFile(relativePath: 'elsewhere/Cached.php', className: 'Cached');
        $this->writeCacheFile(cachedClasses: ['fixture\Cached' => $classFilePath]);
        Autoloader::register(cacheFilePath: $this->cacheFilePath);

        $classExists = class_exists('fixture\Cached');

        self::assertTrue($classExists);
    }

    #[RunInSeparateProcess]
    public function testCachedClassIsIncludedWithoutCheckingItsFileFirst(): void
    {
        stream_wrapper_register('memory', InMemoryStreamWrapper::class);
        InMemoryStreamWrapper::$files['memory://Unchecked.php'] = "<?php\n\nnamespace fixture;\n\nfinal class Unchecked {}\n";
        $this->writeCacheFile(cachedClasses: ['fixture\\Unchecked' => 'memory://Unchecked.php']);
        Autoloader::register(cacheFilePath: $this->cacheFilePath);

        $classExists = class_exists('fixture\Unchecked');

        self::assertTrue($classExists);
        self::assertSame(0, InMemoryStreamWrapper::$urlStatCount);
    }

    #[RunInSeparateProcess]
    public function testMissingCachedFileFallsBackToScanningAndUpdatesCache(): void
    {
        $this->writeCacheFile(cachedClasses: ['fixture\Moved' => $this->temporaryDirectory->path . '/old/Moved.php']);
        $classFilePath = $this->writeClassFile(relativePath: 'src/Moved.php', className: 'Moved');
        $autoloader = Autoloader::register(cacheFilePath: $this->cacheFilePath);
        $autoloader->addPath(autoloaderPath: new AutoloaderPath(
            path: $this->temporaryDirectory->path . '/src/',
            prefix: 'fixture\\',
        ));

        $classExists = class_exists('fixture\Moved');
        $autoloader->__destruct();

        self::assertTrue($classExists);
        self::assertSame(['fixture\Moved' => $classFilePath], $this->includeCacheFile());
    }

    #[RunInSeparateProcess]
    public function testStaleCacheEntryIsRemovedWhenClassIsNotFound(): void
    {
        $this->writeCacheFile(cachedClasses: ['fixture\Deleted' => $this->temporaryDirectory->path . '/Deleted.php']);
        $autoloader = Autoloader::register(cacheFilePath: $this->cacheFilePath);

        $classExists = class_exists('fixture\Deleted');
        $autoloader->__destruct();

        self::assertFalse($classExists);
        self::assertSame([], $this->includeCacheFile());
    }

    #[RunInSeparateProcess]
    public function testWarningsOfCachedClassFileAreNotSuppressed(): void
    {
        $classFilePath = $this->temporaryDirectory->writeFile(
            relativePath: 'Noisy.php',
            content: "<?php\n\nnamespace fixture;\n\ntrigger_error('noisy class file', E_USER_WARNING);\n\nfinal class Noisy {}\n",
        );
        $this->writeCacheFile(cachedClasses: ['fixture\Noisy' => $classFilePath]);
        Autoloader::register(cacheFilePath: $this->cacheFilePath);
        $errorMessages = [];
        set_error_handler(callback: static function (int $errno, string $errstr) use (&$errorMessages): bool {
            $errorMessages[] = $errstr;

            return true;
        });

        try {
            $classExists = class_exists('fixture\Noisy');
        } finally {
            restore_error_handler();
        }

        self::assertTrue($classExists);
        self::assertSame(['noisy class file'], $errorMessages);
    }

    private function writeClassFile(string $relativePath, string $className): string
    {
        return $this->temporaryDirectory->writeFile(
            relativePath: $relativePath,
            content: "<?php\n\nnamespace fixture;\n\nfinal class " . $className . " {}\n",
        );
    }

    /**
     * @param array<string, string> $cachedClasses
     */
    private function writeCacheFile(array $cachedClasses): void
    {
        $this->temporaryDirectory->writeFile(
            relativePath: 'cache/autoloader.php',
            content: '<?php return ' . var_export(value: $cachedClasses, return: true) . ';',
        );
    }

    /**
     * @return array<mixed>
     */
    private function includeCacheFile(): array
    {
        $cachedClasses = include $this->cacheFilePath;
        if (!is_array(value: $cachedClasses)) {
            throw new RuntimeException(message: 'Cache file ' . $this->cacheFilePath . ' does not return an array');
        }

        return $cachedClasses;
    }
}
