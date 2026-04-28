# silverstripe-media-field
Adds a media field with extra options as well as video embeds

## Requirements
* See `composer.json` requirements

## Installation
* `composer require wedevelopnl/silverstripe-media-field`

## Usage
This module introduces a media field to the silverstripe cms.

## License
See [License](LICENSE)

## Maintainers
* [WeDevelop](https://www.wedevelop.nl/) <development@wedevelop.nl>

## Development and contribution
Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.
See read our [contributing](CONTRIBUTING.md) document for more information.

### Getting started

We use [Docker Compose](https://docs.docker.com/compose/) for development. A `Makefile` wraps the common tasks.

#### Bring the stack up

- `make build` — build the image without starting.
- `make up` — start services (builds if needed). The CMS becomes available at the URL printed by the command.
- `make down` — stop services.
- `make destroy` — stop services and remove volumes (resets the test database).

#### Run checks

- `make test` — run PHPUnit.
- `make analyse` — run PHPStan static analysis (level 9).
- `make test-cs` — run php-cs-fixer in dry-run mode.
- `make fix-cs` — auto-fix code style.

`make sh` opens a shell inside the app container.

## Upgrade notes

### 6.0 — `saveEmbed()` signature change

`MediaField::saveEmbed()` now takes a required `Embed\Embed` instance as its second argument. Update consumer code:

```php
// Before
MediaField::saveEmbed($this);

// After
use Embed\Embed;
MediaField::saveEmbed($this, new Embed());
```

The remaining field-name arguments are unchanged. This change unblocks unit tests that exercise the embed-resolution branches without making real network requests.
