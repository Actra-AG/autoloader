# AGENTS.md

Persistent instructions for developers and AI assistants working in this repository.

## Global standard

This project follows the Actra coding standard, installed as development dependency `actra/coding-standard`
(https://github.com/Actra-AG/coding-standard).

- Read [vendor/actra/coding-standard/AGENTS.md](vendor/actra/coding-standard/AGENTS.md) and the standards linked
  there before working on this project. They are binding. If `vendor/` is missing, run `composer install` first.
- The rules below only **add** project-specific rules or state explicit deviations (with reason). They take precedence
  over the global standard where they conflict.

## Project context

- Public library `actra/autoloader` (Packagist): PSR-4/PSR-0 autoloader with a class map cache file, also usable
  without Composer (`require_once 'src/Autoloader.php'`).
- Minimum PHP version 8.5. Released with Git tags, see `UPGRADE.md`.

## Project-specific rules

- No runtime dependencies. `src/Autoloader.php` loads the other classes of `src/` with `require_once`; add new classes
  there too.
- Without a local PHP 8.5, run the Composer commands through DDEV: `ddev start`, then `ddev composer check`.
- Tests that need the registered autoloader run with `#[RunInSeparateProcess]`, because it is registered once per
  process.

## Deviations from the global standard

- `composer test` runs PHPUnit with `opcache.enable_cli=1` and `opcache.file_update_protection=0`, so the OPcache
  invalidation of the cache file is tested.
