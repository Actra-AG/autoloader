# PHP Autoloader

A simple, lightweight, and efficient PHP Autoloader that supports PSR-4, PSR-0, filesystem caching, and custom file suffix lists. This autoloader is designed to be easy to integrate into any PHP project without the overhead of complex dependency management if you need a standalone solution.

## Features

- **PSR-4 and PSR-0 Support**: Load classes according to modern and legacy standards.
- **Filesystem Caching**: Boost performance by caching class-to-file mappings in a PHP file.
- **Custom Suffixes**: Supports multiple file extensions (e.g., `.php`, `.inc.php`, `.class.php`).
- **Namespace Prefixes**: Easily map namespaces or prefixes to specific directories.
- **Lightweight**: Zero external dependencies.

## Installation

### Via Composer (Recommended)

To install the autoloader via Composer, run:

```bash
composer require actra/autoloader
```

### Standalone (Manual)

If you're not using Composer, simply include the source files in your project:

```php
require_once 'src/Autoloader.php';
```

## Basic Usage

### PSR-4 Autoloading

```php
use actra\autoloader\Autoloader;
use actra\autoloader\AutoloaderPath;

// Register the autoloader
$autoloader = Autoloader::register();

// Add a path for a namespace
$autoloader->addPath(new AutoloaderPath(
    path: __DIR__ . '/src',
    prefix: 'MyProject\\'
));
```

### PSR-0 Autoloading (Legacy)

```php
use actra\autoloader\Autoloader;
use actra\autoloader\AutoloaderPath;
use actra\autoloader\AutoloaderPathMode;

$autoloader = Autoloader::register();

$autoloader->addPath(new AutoloaderPath(
    path: __DIR__ . '/lib',
    prefix: 'Legacy_',
    autoloaderPathMode: AutoloaderPathMode::PSR0
));
```

## Advanced Features

### Filesystem Caching

To enable caching, provide a path to a writable PHP file when registering the autoloader. This significantly speeds up class loading in production environments by leveraging OPcache.

```php
$autoloader = Autoloader::register(__DIR__ . '/cache/autoloader.php');
```

The cache is automatically updated and saved when the script finishes execution (using a destructor).

### Custom File Suffixes

If your project uses non-standard file extensions, you can specify them in the `AutoloaderPath` constructor:

```php
$autoloader->addPath(new AutoloaderPath(
    path: __DIR__ . '/src',
    prefix: 'App\\',
    fileSuffixList: ['.php', '.class.php', '.inc']
));
```

## Requirements

- PHP 8.2 or higher (Uses `readonly` classes and enums)

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---
© Actra AG - [https://www.actra.ch](https://www.actra.ch)