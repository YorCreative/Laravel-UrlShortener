# Changelog

## Unreleased

- Add Laravel 13 compatibility and update CI coverage for Laravel 12/13 on PHP 8.2-8.5.
- Add redirect-specific click tracking that avoids reloading the short URL during redirect handling.
- Keep expired redirect events on a fully-loaded `ShortUrl` payload.
- Add DNS-based private IP validation for shortened URLs and document its fail-open lookup behavior.
- Treat `withOpenLimit(0)` as unlimited and reject negative open limits.
- Allow filtering clicks by failure activation outcomes and validate status filters as arrays.
- Treat non-expiring URLs as active and normalize expiring status timestamp comparisons.
- Make registered `withPrefix()` overrides affect generated URLs and reject unregistered prefixes that package routes cannot resolve.
- Add tagged publishing for config, views, and migrations.
- Add Composer export cleanup via `.gitattributes`.
- Remove `minimum-stability: dev` after validating Laravel 12/13 dependency resolution with stable packages.
- Fix README click filter examples and update documented requirements for Laravel 13.
- Convert PHPUnit doc-comment metadata to attributes to remove PHPUnit 11 deprecation notices.
