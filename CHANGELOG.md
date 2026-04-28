# Changelog

## [Unreleased]

### Added

- Automated test suite (PHPUnit) covering `MediaType`, `MediaField` constructor/wrapper logic, and `MediaField::saveEmbed()` branch behaviour.
- GitHub Actions CI: `code-style` (php-cs-fixer) and `php-qa` (PHPStan + PHPUnit) matrix across PHP 8.3, 8.4, and 8.5.
- Dependabot for `composer`, `docker`, and `github-actions` ecosystems.
- FrankenPHP-based Docker stack at `.docker/` that loads a real SilverStripe runtime, replacing the previous `php-cli` Alpine image.

### Changed

- **BC break:** `MediaField::saveEmbed()` now requires an `Embed\Embed` instance as its second argument (was previously created internally). Update callers from `MediaField::saveEmbed($this)` to `MediaField::saveEmbed($this, new Embed())`.
- `Makefile` rewritten around the new Docker stack. `make test` now runs PHPUnit (was: php-cs-fixer dry-run); use `make test-cs` for the latter.
- PHPStan config moved from root `phpstan.neon` to `.docker/app/phpstan.neon.dist` (level unchanged).

### Removed

- Root `Dockerfile`, `compose.yml`, and `dev/` directory — superseded by `.docker/`.
- `friendsofphp/php-cs-fixer` from module `require-dev` — moved to the test app's composer manifest at `.docker/app/composer.json`.
