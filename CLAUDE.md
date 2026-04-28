# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

Silverstripe 6 module (`wedevelopnl/silverstripe-media-field`) that exposes a single composite form field (`MediaField`) for selecting between an uploaded image or a video URL whose oEmbed metadata is automatically resolved.

PHP 8.3+, namespace `WeDevelop\MediaField\` rooted at `src/`.

## Common commands

All tooling runs inside the bundled Docker container. Targets auto-detect whether you're already inside the container — if `pwd` is `/app` they run directly, otherwise they prefix `docker compose exec php`.

- `make build` — build the container and start it detached (first-time setup).
- `make up` / `make down` — start / stop the container.
- `make sh` — shell into the container.
- `make test` — code-style check (`php-cs-fixer fix --diff --dry-run`). This is the "test" target — there is no PHPUnit suite.
- `make fix-cs` — auto-fix code style.
- `make phpstan` — static analysis (level 9, Silverstan extension).

To run a single tool against a single file from inside the container: `./vendor/bin/php-cs-fixer fix path/to/File.php` or `./vendor/bin/phpstan analyse path/to/File.php`.

## Architecture

The whole module is two classes plus one YAML config:

- `src/Form/MediaField.php` — `CompositeField` subclass. The constructor accepts an existing `FieldList` and the names of the four DB fields the consuming `DataObject` uses (`MediaType`, `MediaImage`, `MediaVideoFullURL`, plus an upload folder). It removes those fields from the parent list and re-adds them inside the composite, wrapped in `UncleCheese\DisplayLogic` `Wrapper`s so that the image/video inputs show/hide based on the type dropdown. `FieldHolder()` is overridden to wire the display-logic conditions late — they reference `$this->typeField` so they need the field name captured in the constructor.
- `src/Form/MediaType.php` — backed enum (`image` / `video`). Note: `MediaType::toDropdownSource()` exists, but `MediaField` does **not** use it — it builds its own dropdown so it can filter by the `enabled_types` config. Treat the enum as the source of truth for valid type values.
- `_config/config.yml` — toggles `MediaField::$enabled_types` per media type. Disabling a type both removes it from the dropdown and skips constructing its wrapper/upload field, so `getImageWrapper()` / `getVideoWrapper()` may legitimately return `null`.

### How consumers integrate

The module does **not** define a `DataExtension` or schema. Consumers add their own DB fields to a `DataObject`, then in `getCMSFields()` they pass the field list to `MediaField`'s constructor. To populate the embed metadata fields (provider, embedded URL, thumbnail, etc.) consumers must call the static `MediaField::saveEmbed($object, ...)` from their own `onBeforeWrite()` — the field itself does not hook the write lifecycle.

`saveEmbed()` uses `embed/embed` to fetch oEmbed data and only re-fetches when `MediaVideoFullURL` changed or the embedded URL is empty. Vimeo is special-cased to pull `thumbnail_url` and `upload_date` from the raw oEmbed payload because `embed/embed` doesn't surface them on its primary API for that provider.

## Code style

- `.php-cs-fixer.php` at the root: PSR-12 + `@PHP83Migration`, strict comparison, short array syntax, no `declare(strict_types=1)` yet (TODO in the file once PHPStan is fully clean).
- PHPStan runs at **level 9** with `treatPhpDocTypesAsCertain: false`. New code is expected to type-hint thoroughly enough to pass at this level.
- There is a stray `src/Form/.php-cs-fixer.dist.php` (untracked) which is **not** the active config — the root `.php-cs-fixer.php` is what `make test` and `make fix-cs` use.

## Distribution

`.gitattributes` marks dev files (`Dockerfile`, `Makefile`, `compose.yml`, `dev/`, `phpstan.neon`, `.php-cs-fixer.php`, etc.) as `export-ignore` so they are excluded from Packagist tarballs. When adding new dev-only files at the root, add a matching `export-ignore` entry.
