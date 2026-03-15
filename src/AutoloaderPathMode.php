<?php
/**
 * @copyright Actra AG - https://www.actra.ch
 * @license   MIT
 */

declare(strict_types=1);

namespace actra\autoloader;

enum AutoloaderPathMode: string
{
    case PSR4 = 'PSR4';
    case PSR0 = 'PSR0';

    public function getDelimiter(): string
    {
        return match ($this) {
            AutoloaderPathMode::PSR4 => '\\',
            AutoloaderPathMode::PSR0 => '_',
        };
    }
}