# Changelog

## [Unreleased]

## [6.0.0] - 2026-11-10

First stable release for Silverstripe CMS 6. Covers all changes since 4.0.1, including those shipped in 6.0.0-RC1 to RC3.

### Added

- Silverstripe CMS 6 support.
- `MediaType` backed enum (`image` / `video`) with `label()` and `toDropdownSource()`.
- `MediaField.enabled_types` config to switch off the image or video option: a disabled type is left out of the dropdown and its input isn't built.
- `VideoEmbedResolver` service (`VideoEmbedResolver::singleton()->resolve($this)`) that fills in video details using the module's own connection (`_config/embed.yml`): embed/embed's CurlClient with a browser `User-Agent` that projects can override in YAML.
- A refused video URL (internal network, missing scheme, credentials, disallowed port) or an unreachable video site now shows a validation message on the video URL field instead of an unhandled error; refused addresses are logged as a warning.
- Automated test suite (PHPUnit) covering `MediaType`, `MediaField` constructor/wrapper logic, and `VideoEmbedResolver` branch behaviour and wiring.
- GitHub Actions CI: `code-style` (php-cs-fixer), `static-analysis` (PHPStan), and `phpunit` matrix across PHP 8.3, 8.4, and 8.5.
- Dependabot for `composer`, `docker`, and `github-actions` ecosystems.
- FrankenPHP-based Docker stack at `.docker/` that loads a real SilverStripe runtime, replacing the previous `php-cli` Alpine image.

### Changed

- **BC break:** requires PHP `^8.3`, `silverstripe/cms` `^6`, `silverstripe/asset-admin` `^3`, and `unclecheese/display-logic` `^4` (replaces `silverstripe/display-logic` `^3`).
- **BC break:** `MediaField::saveEmbed()` is removed. Replace `MediaField::saveEmbed($this)` (4.x) or `MediaField::saveEmbed($this, new Embed())` (6.0.0-RC1 to RC3) with `VideoEmbedResolver::singleton()->resolve($this)`; custom field-name arguments keep their order.
- **BC break:** `MediaField::getImageWrapper()`, `getVideoWrapper()` and `getImageUploadField()` are now nullable; they return `null` when that type is disabled in `enabled_types`.
- The video creation date of non-Vimeo videos is stored as `DateTimeInterface::ATOM` (`2026-01-22T10:00:00+01:00`) instead of the deprecated `DateTime::ISO8601` (`+0100`). Existing records keep their old value until the video URL changes.
- `embed/embed` constraint raised to `^4.5.4` (address policy and TLS verification on by default).
- `Makefile` rewritten around the new Docker stack. `make test` now runs PHPUnit (was: php-cs-fixer dry-run); use `make test-cs` for the latter.
- PHPStan config moved from root `phpstan.neon` to `.docker/app/phpstan.neon.dist` (level unchanged).

### Removed

- **BC break:** `MediaField::TYPE_IMAGE` and `MediaField::TYPE_VIDEO` constants. Use `MediaType::Image->value` and `MediaType::Video->value`.
- Root `Dockerfile`, `compose.yml`, and `dev/` directory — superseded by `.docker/`.
- `friendsofphp/php-cs-fixer` from module `require-dev` — moved to the test app's composer manifest at `.docker/app/composer.json`.

[Unreleased]: https://github.com/wedevelopnl/silverstripe-media-field/compare/6.0.0...6
[6.0.0]: https://github.com/wedevelopnl/silverstripe-media-field/compare/4.0.1...6.0.0
