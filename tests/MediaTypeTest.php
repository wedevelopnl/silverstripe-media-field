<?php

namespace WeDevelop\MediaField\Tests;

use PHPUnit\Framework\TestCase;
use WeDevelop\MediaField\Form\MediaType;

class MediaTypeTest extends TestCase
{
    public function testLabelReturnsHumanString(): void
    {
        self::assertSame('Image', MediaType::Image->label());
        self::assertSame('Video', MediaType::Video->label());
    }

    public function testToDropdownSourceReturnsValueLabelMap(): void
    {
        self::assertSame(
            ['image' => 'Image', 'video' => 'Video'],
            MediaType::toDropdownSource(),
        );
    }
}
