<?php

/**
 * @copyright Actra AG - https://www.actra.ch
 * @license   MIT
 */

declare(strict_types=1);

namespace actra\autoloader\tests\Integration;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

/**
 * Directory below the system temp directory for files created by a test, removed with remove().
 */
final readonly class TemporaryDirectory
{
    public string $path;

    public function __construct()
    {
        $this->path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'actra-autoloader-test-' . bin2hex(string: random_bytes(length: 8));
        if (!mkdir(directory: $this->path, recursive: true)) {
            throw new RuntimeException(message: 'Temporary directory ' . $this->path . ' could not be created');
        }
    }

    public function writeFile(string $relativePath, string $content): string
    {
        $filePath = $this->path . DIRECTORY_SEPARATOR . $relativePath;
        $directory = dirname(path: $filePath);
        if (!is_dir(filename: $directory) && !mkdir(directory: $directory, recursive: true)) {
            throw new RuntimeException(message: 'Directory ' . $directory . ' could not be created');
        }
        if (file_put_contents(filename: $filePath, data: $content) === false) {
            throw new RuntimeException(message: 'File ' . $filePath . ' could not be written');
        }

        return $filePath;
    }

    /**
     * @return list<string>
     */
    public function fileNames(): array
    {
        $fileNames = scandir(directory: $this->path);
        if ($fileNames === false) {
            throw new RuntimeException(message: 'Directory ' . $this->path . ' could not be read');
        }

        return array_values(array: array_diff($fileNames, ['.', '..']));
    }

    public function remove(): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        /** @var SplFileInfo $fileInfo */
        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isDir()) {
                rmdir(directory: $fileInfo->getPathname());
                continue;
            }
            unlink(filename: $fileInfo->getPathname());
        }
        rmdir(directory: $this->path);
    }
}
