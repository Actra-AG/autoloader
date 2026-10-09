<?php

/**
 * @copyright Actra AG - https://www.actra.ch
 * @license   MIT
 */

declare(strict_types=1);

namespace actra\autoloader\tests\Double;

/**
 * Stream wrapper serving files from memory and counting stat calls (file_exists(), is_file(), …) on them.
 * Register it with stream_wrapper_register(); its state is static because PHP instantiates the wrapper itself.
 */
final class InMemoryStreamWrapper
{
    /** @var array<string, string> */
    public static array $files = [];
    public static int $urlStatCount = 0;

    /** @var resource|null set by PHP */
    public $context;
    private string $content = '';
    private int $position = 0;

    public function stream_open(string $path, string $mode, int $options, ?string &$openedPath): bool
    {
        if (!array_key_exists(key: $path, array: InMemoryStreamWrapper::$files)) {
            return false;
        }
        $this->content = InMemoryStreamWrapper::$files[$path];
        $this->position = 0;

        return true;
    }

    public function stream_read(int $count): string
    {
        $chunk = substr(string: $this->content, offset: $this->position, length: $count);
        $this->position += strlen(string: $chunk);

        return $chunk;
    }

    public function stream_eof(): bool
    {
        return $this->position >= strlen(string: $this->content);
    }

    /**
     * @return array<string, int>
     */
    public function stream_stat(): array
    {
        return ['size' => strlen(string: $this->content)];
    }

    public function stream_set_option(int $option, int $arg1, ?int $arg2): bool
    {
        return false;
    }

    /**
     * @return array<string, int>|false
     */
    public function url_stat(string $path, int $flags): array|false
    {
        InMemoryStreamWrapper::$urlStatCount++;
        if (!array_key_exists(key: $path, array: InMemoryStreamWrapper::$files)) {
            return false;
        }

        return ['mode' => 0o100644, 'size' => strlen(string: InMemoryStreamWrapper::$files[$path])];
    }
}
