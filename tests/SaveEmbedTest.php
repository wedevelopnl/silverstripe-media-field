<?php

namespace WeDevelop\MediaField\Tests;

use Embed\Embed;
use PHPUnit\Framework\MockObject\MockObject;
use SilverStripe\Dev\SapphireTest;
use WeDevelop\MediaField\Form\MediaField;
use WeDevelop\MediaField\Tests\Stub\MediaFieldDataObjectStub;

class SaveEmbedTest extends SapphireTest
{
    protected static $extra_dataobjects = [
        MediaFieldDataObjectStub::class,
    ];

    public function testSkipsWhenVideoUrlIsEmpty(): void
    {
        /** @var Embed&MockObject $embed */
        $embed = $this->createMock(Embed::class);
        $embed->expects($this->never())->method('get');

        $object = MediaFieldDataObjectStub::create();
        $object->MediaVideoFullURL = '';

        MediaField::saveEmbed($object, $embed);

        self::assertSame('', (string) $object->MediaVideoEmbeddedURL);
        self::assertSame('', (string) $object->MediaVideoProvider);
    }

    public function testSkipsWhenUrlUnchangedAndEmbeddedUrlAlreadyPopulated(): void
    {
        /** @var Embed&MockObject $embed */
        $embed = $this->createMock(Embed::class);
        $embed->expects($this->never())->method('get');

        $object = MediaFieldDataObjectStub::create();
        $object->MediaVideoFullURL = 'https://www.youtube.com/watch?v=abc';
        $object->MediaVideoEmbeddedURL = 'https://www.youtube.com/embed/abc';
        $object->write();

        $reloaded = MediaFieldDataObjectStub::get()->byID($object->ID);
        self::assertNotNull($reloaded);
        self::assertFalse($reloaded->isChanged('MediaVideoFullURL'));

        MediaField::saveEmbed($reloaded, $embed);

        self::assertSame('', (string) $reloaded->MediaVideoProvider);
    }

    public function testReturnsEarlyWhenIframeCodeHasNoSrc(): void
    {
        $extractor = $this->createMock(\Embed\Extractor::class);
        $extractor->method('__get')->willReturnMap([
            ['code', new \Embed\EmbedCode('<div>no iframe here</div>')],
        ]);

        /** @var Embed&MockObject $embed */
        $embed = $this->createMock(Embed::class);
        $embed->method('get')->willReturn($extractor);

        $object = MediaFieldDataObjectStub::create();
        $object->MediaVideoFullURL = 'https://example.com/no-iframe';

        MediaField::saveEmbed($object, $embed);

        self::assertSame('', (string) $object->MediaVideoEmbeddedURL);
        self::assertSame('', (string) $object->MediaVideoProvider);
    }

    public function testVimeoBranchPullsFromOEmbedPayload(): void
    {
        $oembed = $this->createMock(\Embed\OEmbed::class);
        $oembed->method('get')->willReturnCallback(fn(string ...$keys): ?string => match ($keys[0] ?? null) {
            'thumbnail_url' => 'https://i.vimeocdn.com/video/123_640.jpg',
            'upload_date' => '2024-05-12T10:00:00+00:00',
            default => null,
        });

        $extractor = $this->createMock(\Embed\Extractor::class);
        $extractor->method('__get')->willReturnMap([
            ['code', new \Embed\EmbedCode('<iframe src="https://player.vimeo.com/video/123" width="640" height="360"></iframe>')],
            ['providerName', 'Vimeo'],
            ['title', 'Test Vimeo Video'],
            ['description', 'desc'],
        ]);
        $extractor->method('getOEmbed')->willReturn($oembed);

        /** @var Embed&MockObject $embed */
        $embed = $this->createMock(Embed::class);
        $embed->method('get')->willReturn($extractor);

        $object = MediaFieldDataObjectStub::create();
        $object->MediaVideoFullURL = 'https://vimeo.com/123';

        MediaField::saveEmbed($object, $embed);

        self::assertSame('https://player.vimeo.com/video/123', (string) $object->MediaVideoEmbeddedURL);
        self::assertSame('Vimeo', (string) $object->MediaVideoProvider);
        self::assertSame('Test Vimeo Video', (string) $object->MediaVideoEmbeddedName);
        self::assertSame('https://i.vimeocdn.com/video/123_640.jpg', (string) $object->MediaVideoEmbeddedThumbnail);
        self::assertSame('2024-05-12T10:00:00+00:00', (string) $object->MediaVideoEmbeddedCreated);
    }

    public function testNonVimeoBranchUsesImageAndPublishedTime(): void
    {
        $publishedAt = new \DateTime('2024-03-01T12:00:00+00:00');

        $imageUri = $this->createMock(\Psr\Http\Message\UriInterface::class);
        $imageUri->method('__toString')->willReturn('https://i.ytimg.com/vi/abc/hqdefault.jpg');

        $extractor = $this->createMock(\Embed\Extractor::class);
        $extractor->method('__get')->willReturnMap([
            ['code', new \Embed\EmbedCode('<iframe src="https://www.youtube.com/embed/abc" width="640" height="360"></iframe>')],
            ['providerName', 'YouTube'],
            ['title', 'Test YouTube Video'],
            ['description', 'desc'],
            ['image', $imageUri],
            ['publishedTime', $publishedAt],
        ]);

        /** @var Embed&MockObject $embed */
        $embed = $this->createMock(Embed::class);
        $embed->method('get')->willReturn($extractor);

        $object = MediaFieldDataObjectStub::create();
        $object->MediaVideoFullURL = 'https://www.youtube.com/watch?v=abc';

        MediaField::saveEmbed($object, $embed);

        self::assertSame('https://www.youtube.com/embed/abc', (string) $object->MediaVideoEmbeddedURL);
        self::assertSame('YouTube', (string) $object->MediaVideoProvider);
        self::assertSame('https://i.ytimg.com/vi/abc/hqdefault.jpg', (string) $object->MediaVideoEmbeddedThumbnail);
        self::assertSame($publishedAt->format(\DateTimeInterface::ATOM), (string) $object->MediaVideoEmbeddedCreated);
    }
}
