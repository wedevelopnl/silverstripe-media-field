# Changelog

## [Unreleased]

### Added

- `VideoEmbedResolver` service (`VideoEmbedResolver::singleton()->resolve($this)`) that fills in video details using the module's own connection (`_config/embed.yml`): embed/embed's CurlClient with a browser `User-Agent` that projects can override in YAML.
- A refused video URL (internal network, missing scheme, credentials, disallowed port) or an unreachable video site now shows a validation message on the video URL field instead of an unhandled error; refused addresses are logged as a warning.
- Automated test suite (PHPUnit) covering `MediaType`, `MediaField` constructor/wrapper logic, and `VideoEmbedResolver` branch behaviour and wiring.
- GitHub Actions CI: `code-style` (php-cs-fixer), `static-analysis` (PHPStan), and `phpunit` matrix across PHP 8.3, 8.4, and 8.5.
- Dependabot for `composer`, `docker`, and `github-actions` ecosystems.
- FrankenPHP-based Docker stack at `.docker/` that loads a real SilverStripe runtime, replacing the previous `php-cli` Alpine image.

### Changed

- **BC break:** `MediaField::saveEmbed()` is removed. Replace `MediaField::saveEmbed($this)` (4.x) or `MediaField::saveEmbed($this, new Embed())` (6.0.0-RC1 to RC3) with `VideoEmbedResolver::singleton()->resolve($this)`; custom field-name arguments keep their order.
- `embed/embed` constraint raised to `^4.5.4` (address policy and TLS verification on by default).
- `Makefile` rewritten around the new Docker stack. `make test` now runs PHPUnit (was: php-cs-fixer dry-run); use `make test-cs` for the latter.
- PHPStan config moved from root `phpstan.neon` to `.docker/app/phpstan.neon.dist` (level unchanged).

### Removed

- Root `Dockerfile`, `compose.yml`, and `dev/` directory — superseded by `.docker/`.
- `friendsofphp/php-cs-fixer` from module `require-dev` — moved to the test app's composer manifest at `.docker/app/composer.json`.
