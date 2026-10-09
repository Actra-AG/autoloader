<?php

/**
 * @copyright Actra AG - https://www.actra.ch
 * @license   MIT
 */

declare(strict_types=1);

namespace actra\autoloader;

readonly class AutoloaderPath
{
    public string $path;

    /**
     * @param list<string> $fileSuffixList
     */
    public function __construct(
        string $path,
        public string $prefix,
        public AutoloaderPathMode $autoloaderPathMode = AutoloaderPathMode::PSR4,
        /** @var list<string> */
        public array $fileSuffixList = ['.php'],
    ) {
        $this->path = str_replace(
            search: '\\',
            replace: '/',
            subject: $path,
        );
    }
}
