<?php

/**
 * @copyright Actra AG - https://www.actra.ch
 * @license   MIT
 */

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

/** @var array<string, bool|array<string, mixed>> $rules */
$rules = require __DIR__ . '/vendor/actra/coding-standard/config/php-cs-fixer.php';

return new Config()
    ->setRiskyAllowed(true)
    ->setParallelConfig(ParallelConfigFactory::detect())
    ->setRules([
        ...$rules,
        // Adapt @license to the project (MIT for public libraries, proprietary for closed projects)
        'header_comment' => [
            'header' => "@copyright Actra AG - https://www.actra.ch\n@license   MIT",
            'comment_type' => 'PHPDoc',
            'location' => 'after_open',
            'separate' => 'both',
        ],
    ])
    ->setFinder(
        Finder::create()
            ->in([
                __DIR__ . '/src',
                __DIR__ . '/tests',
            ])
            ->append([__FILE__]),
    );
