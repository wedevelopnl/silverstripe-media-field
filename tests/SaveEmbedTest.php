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
}
