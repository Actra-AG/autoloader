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

    public function __construct(
        string $path,
        public string $prefix,
        public AutoloaderPathMode $autoloaderPathMode = AutoloaderPathMode::PSR4,
        public array $fileSuffixList = ['.php']
    ) {
        $this->path = str_replace(
            search: '\\',
            replace: '/',
            subject: $path
        );
    }
}