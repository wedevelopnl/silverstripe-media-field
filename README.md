# silverstripe-media-field
Adds a media field with extra options as well as video embeds

## Requirements
* See `composer.json` requirements

## Installation
* `composer require wedevelopnl/silverstripe-media-field`

## Usage
This module introduces a media field to the silverstripe cms.

Add the media DB fields to your `DataObject`, pass its field list to `MediaField` in `getCMSFields()`, and let the module fill in the video details on save:

```php
use WeDevelop\MediaField\Service\VideoEmbedResolver;

protected function onBeforeWrite(): void
{
    parent::onBeforeWrite();
    VideoEmbedResolver::singleton()->resolve($this);
}
```

Pass your own field names after `$this` when they differ from the defaults (`MediaVideoFullURL`, `MediaVideoEmbeddedURL`, `MediaVideoProvider`, `MediaVideoEmbeddedName`, `MediaVideoEmbeddedDescription`, `MediaVideoEmbeddedThumbnail`, `MediaVideoEmbeddedCreated`).

When the video URL points to an address the module refuses to fetch (an internal network, no `http(s)://`, credentials in the URL, a disallowed port), or the video site can't be reached, `resolve()` throws a `ValidationException` with a message on the video URL field, so the CMS shows it under that field and the record isn't saved. Refused addresses are logged as a warning.

### Media types

Both image and video are offered by default. To offer only one, switch the other off in your project's config; it disappears from the type dropdown and its input isn't built:

```yaml
WeDevelop\MediaField\Form\MediaField:
  enabled_types:
    video: false
```

### Browser identification

Some video sites refuse requests from outdated browsers, so the module identifies itself with a browser `User-Agent`. It uses its own connection (`Embed\Embed.mediafield` in `_config/embed.yml`), separate from the framework's oEmbed service. To send a different identification, add one more `calls:` entry in your project's config; the last one wins:

```yaml
---
Name: app-mediafield-embed
After: mediafield-embed
---
SilverStripe\Core\Injector\Injector:
  Embed\Http\Crawler.mediafield:
    calls:
      - [addDefaultHeaders, [{User-Agent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36'}]]
```

Don't override `constructor:` the same way: config merging appends list entries, so it would add an argument instead of replacing one.

## License
See [License](LICENSE)

## Maintainers
* [WeDevelop](https://www.wedevelop.nl/) <development@wedevelop.nl>

## Development and contribution
Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

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

### 6.0

Version 6 requires PHP 8.3+ and Silverstripe CMS 6 (`silverstripe/asset-admin` `^3`). Display logic now comes from `unclecheese/display-logic` `^4`, which replaces `silverstripe/display-logic`; if your project requires `silverstripe/display-logic` itself, swap it for `unclecheese/display-logic`.

#### `MediaField::saveEmbed()` replaced by `VideoEmbedResolver`

Fetching video details moved off the form field into an injectable service that sets up its own connection to video sites. Replace the call in your `onBeforeWrite()`, whichever form you have:

```php
// Before (4.x)
MediaField::saveEmbed($this);

// Before (6.0.0-RC1 to RC3)
MediaField::saveEmbed($this, new Embed());

// After
use WeDevelop\MediaField\Service\VideoEmbedResolver;
VideoEmbedResolver::singleton()->resolve($this);
```

Custom field-name arguments keep their order; drop the `Embed` argument if you passed one. `resolve()` can now throw a `ValidationException` for a refused or unreachable video URL (see Usage). `embed/embed` is required at `^4.5.4`.

#### `TYPE_IMAGE` / `TYPE_VIDEO` replaced by the `MediaType` enum

```php
// Before
MediaField::TYPE_VIDEO

// After
use WeDevelop\MediaField\Form\MediaType;
MediaType::Video->value
```

The stored values (`image`, `video`) are unchanged, so existing records need no migration.

#### Wrapper getters can return `null`

`getImageWrapper()`, `getVideoWrapper()` and `getImageUploadField()` return `null` when that type is switched off in `enabled_types` (see Media types). Both types are on by default, so this only affects projects that switch one off; use `?->` on these getters if yours does.

#### Video creation date format

The creation date of non-Vimeo videos is now stored as `2026-01-22T10:00:00+01:00` instead of `2026-01-22T10:00:00+0100`. Existing records keep the old value until their video URL changes. Code that reads the field with `DateTime` or `strtotime()` handles both; code that compares or slices the raw string needs to accept both.
