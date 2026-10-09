<?php

/**
 * @copyright Actra AG - https://www.actra.ch
 * @license   MIT
 */

declare(strict_types=1);

namespace actra\autoloader\tests\Integration;

use actra\autoloader\CacheFileWriter;
use Override;
use PHPUnit\Framework\Attributes\RequiresSetting;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class CacheFileWriterTest extends TestCase
{
    private TemporaryDirectory $temporaryDirectory;
    private string $cacheFilePath;

    #[Override]
    protected function setUp(): void
    {
        $this->temporaryDirectory = new TemporaryDirectory();
        $this->cacheFilePath = $this->temporaryDirectory->path . DIRECTORY_SEPARATOR . 'autoloader.php';
    }

    #[Override]
    protected function tearDown(): void
    {
        $this->temporaryDirectory->remove();
    }

    public function testWriteCreatesIncludableCacheFile(): void
    {
        $cacheFileWriter = new CacheFileWriter();

        $written = $cacheFileWriter->write(
            cacheFilePath: $this->cacheFilePath,
            cachedClasses: ['Example\Foo' => '/example/Foo.php'],
        );

        self::assertTrue($written);
        self::assertSame(['Example\Foo' => '/example/Foo.php'], $this->includeCacheFile());
    }

    public function testWriteReplacesExistingFileInsteadOfOverwritingItsContent(): void
    {
        $this->temporaryDirectory->writeFile(
            relativePath: 'autoloader.php',
            content: "<?php return ['Example\\\\Old' => '/example/Old.php'];",
        );
        // A request that is including the old file still sees it complete: the link keeps the old content
        $linkPath = $this->temporaryDirectory->path . DIRECTORY_SEPARATOR . 'link.php';
        self::assertTrue(link(target: $this->cacheFilePath, link: $linkPath));
        $cacheFileWriter = new CacheFileWriter();

        $cacheFileWriter->write(
            cacheFilePath: $this->cacheFilePath,
            cachedClasses: ['Example\New' => '/example/New.php'],
        );

        self::assertSame(['Example\Old' => '/example/Old.php'], include $linkPath);
        self::assertSame(['Example\New' => '/example/New.php'], $this->includeCacheFile());
    }

    public function testWriteLeavesNoTemporaryFile(): void
    {
        $cacheFileWriter = new CacheFileWriter();

        $cacheFileWriter->write(
            cacheFilePath: $this->cacheFilePath,
            cachedClasses: ['Example\Foo' => '/example/Foo.php'],
        );

        self::assertSame(['autoloader.php'], $this->temporaryDirectory->fileNames());
    }

    #[RequiresSetting('opcache.enable_cli', '1')]
    #[RequiresSetting('opcache.file_update_protection', '0')]
    public function testRewrittenCacheFileIsIncludedWithNewContentDespiteOpcache(): void
    {
        $cacheFileWriter = new CacheFileWriter();
        $cacheFileWriter->write(
            cacheFilePath: $this->cacheFilePath,
            cachedClasses: ['Example\Old' => '/example/Old.php'],
        );
        // Compiles the file into OPcache, which would serve it until the next timestamp check
        $this->includeCacheFile();

        $cacheFileWriter->write(
            cacheFilePath: $this->cacheFilePath,
            cachedClasses: ['Example\New' => '/example/New.php'],
        );

        self::assertSame(['Example\New' => '/example/New.php'], $this->includeCacheFile());
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
