<?php

/**
 * @copyright Actra AG - https://www.actra.ch
 * @license   MIT
 */

declare(strict_types=1);

namespace actra\autoloader;

/**
 * Writes the class map cache atomically, so concurrent requests never include a half-written file.
 *
 * @internal
 */
final class CacheFileWriter
{
    /**
     * @param array<string, string> $cachedClasses
     */
    public function write(string $cacheFilePath, array $cachedClasses): bool
    {
        // Same directory as the cache file, so rename() is atomic (same file system)
        $temporaryFilePath = $cacheFilePath . '.' . bin2hex(string: random_bytes(length: 8)) . '.tmp';
        $written = file_put_contents(
            filename: $temporaryFilePath,
            data: '<?php return ' . var_export(value: $cachedClasses, return: true) . ';',
        );
        if ($written === false) {
            return false;
        }
        if (!rename(from: $temporaryFilePath, to: $cacheFilePath)) {
            unlink(filename: $temporaryFilePath);

            return false;
        }
        $this->invalidateOpcache(cacheFilePath: $cacheFilePath);

        return true;
    }

    private function invalidateOpcache(string $cacheFilePath): void
    {
        // A restricted API would emit a warning; OPcache then picks up the new file by its timestamp
        if (ini_get(option: 'opcache.restrict_api') !== '') {
            return;
        }
        // force: the new file may have the same timestamp (in seconds) as the cached one
        opcache_invalidate(filename: $cacheFilePath, force: true);
    }
}
