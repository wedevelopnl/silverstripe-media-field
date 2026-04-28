# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

Silverstripe 6 module (`wedevelopnl/silverstripe-media-field`) that exposes a single composite form field (`MediaField`) for selecting between an uploaded image or a video URL whose oEmbed metadata is automatically resolved.

PHP 8.3+, namespace `WeDevelop\MediaField\` rooted at `src/`.

## Common commands

- `make build` — build the container.
- `make up` / `make down` / `make destroy` — start / stop / wipe the stack.
- `make sh` — shell into the container.
- `make test` — run PHPUnit (defined in `.docker/app/phpunit.xml.dist`).
- `make analyse` — run PHPStan at level 9.
- `make test-cs` — run php-cs-fixer in dry-run mode.
- `make fix-cs` — auto-fix code style.

To run a single tool against a single file from inside the container: `vendor/bin/phpunit --filter <Test>` or `vendor/bin/phpstan analyse /module/src/Form/MediaField.php`.

## Architecture

The whole module is two classes plus one YAML config:

- `src/Form/MediaField.php` — `CompositeField` subclass. The constructor accepts an existing `FieldList` and the names of the four DB fields the consuming `DataObject` uses (`MediaType`, `MediaImage`, `MediaVideoFullURL`, plus an upload folder). It removes those fields from the parent list and re-adds them inside the composite, wrapped in `UncleCheese\DisplayLogic` `Wrapper`s so that the image/video inputs show/hide based on the type dropdown. `FieldHolder()` is overridden to wire the display-logic conditions late — they reference `$this->typeField` so they need the field name captured in the constructor.
- `src/Form/MediaType.php` — backed enum (`image` / `video`). Note: `MediaType::toDropdownSource()` exists, but `MediaField` does **not** use it — it builds its own dropdown so it can filter by the `enabled_types` config. Treat the enum as the source of truth for valid type values.
- `_config/config.yml` — toggles `MediaField::$enabled_types` per media type. Disabling a type both removes it from the dropdown and skips constructing its wrapper/upload field, so `getImageWrapper()` / `getVideoWrapper()` may legitimately return `null`.
- `tests/` — PHPUnit suite. `MediaTypeTest` covers the enum, `MediaFieldTest` (extends `SapphireTest`) covers the composite-field wiring under each `enabled_types` permutation, and `SaveEmbedTest` (extends `SapphireTest`) uses a `MockObject` of `Embed\Embed` to assert each branch of the static helper. The stub DataObject lives at `tests/Stub/MediaFieldDataObjectStub.php`.
- `.docker/` — FrankenPHP+MySQL test stack, modelled on `silverstripe-grid/.docker/`. The module is mounted into the container at `/module` and pulled into the test SilverStripe app via a path repository.
- `.github/workflows/ci.yml` — `code-style` job + `php-qa` matrix (PHP 8.3/8.4/8.5).

### How consumers integrate

The module does **not** define a `DataExtension` or schema. Consumers add their own DB fields to a `DataObject`, then in `getCMSFields()` they pass the field list to `MediaField`'s constructor. To populate the embed metadata fields (provider, embedded URL, thumbnail, etc.) consumers must call `MediaField::saveEmbed($object, new Embed(), ...)` from their own `onBeforeWrite()` — the field itself does not hook the write lifecycle, and `Embed` is now an explicit dependency so the helper is testable without making real HTTP calls.

`saveEmbed()` uses `embed/embed` to fetch oEmbed data and only re-fetches when `MediaVideoFullURL` changed or the embedded URL is empty. Vimeo is special-cased to pull `thumbnail_url` and `upload_date` from the raw oEmbed payload because `embed/embed` doesn't surface them on its primary API for that provider.

## Code style

- `.php-cs-fixer.php` at the root: PSR-12 + `@PHP83Migration`, strict comparison, short array syntax, no `declare(strict_types=1)` yet (TODO in the file once PHPStan is fully clean).
- PHPStan runs at **level 9** with `treatPhpDocTypesAsCertain: false`. New code is expected to type-hint thoroughly enough to pass at this level.

## Distribution

`.gitattributes` marks dev files (`Dockerfile`, `Makefile`, `compose.yml`, `dev/`, `phpstan.neon`, `.php-cs-fixer.php`, etc.) as `export-ignore` so they are excluded from Packagist tarballs. When adding new dev-only files at the root, add a matching `export-ignore` entry.
