# Upgrade

Changes of `actra/autoloader`, newest first. ⚠️ marks breaking changes.

## v1.2.0 (2026-10-09)

### ⚠️ PHP 8.5 required

Before: `"php": ">=8.2"`. After: `"php": ">=8.5"`. Update PHP before updating the autoloader.

### Other changes

- The cache file is written atomically and invalidated in OPcache, so concurrent requests never include a half-written
  file.
- Cached classes are included without checking their file first; stale cache entries are removed automatically.
- `Autoloader` is `final`; `Autoloader::get()` throws a `LogicException` if the autoloader is not registered.
