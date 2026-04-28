<?php

namespace WeDevelop\MediaField\Tests;

use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use UncleCheese\DisplayLogic\Forms\Wrapper;
use WeDevelop\MediaField\Form\MediaField;

class MediaFieldTest extends SapphireTest
{
    public function testConstructorWiresBothImageAndVideoByDefault(): void
    {
        $parent = FieldList::create([
            DropdownField::create('MediaType', 'Type'),
            UploadField::create('MediaImage', 'Image'),
            TextField::create('MediaVideoFullURL', 'Video URL'),
        ]);

        $field = new MediaField($parent);

        self::assertNull($parent->dataFieldByName('MediaType'));
        self::assertNull($parent->dataFieldByName('MediaImage'));
        self::assertNull($parent->dataFieldByName('MediaVideoFullURL'));

        self::assertInstanceOf(Wrapper::class, $field->getImageWrapper());
        self::assertInstanceOf(Wrapper::class, $field->getVideoWrapper());
        self::assertInstanceOf(UploadField::class, $field->getImageUploadField());
    }
}
