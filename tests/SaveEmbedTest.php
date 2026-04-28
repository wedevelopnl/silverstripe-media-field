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
}
