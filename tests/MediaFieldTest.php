<?php

namespace WeDevelop\MediaField\Tests;

use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\TextField;
use UncleCheese\DisplayLogic\Criteria;
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

    public function testConstructorOmitsVideoWhenDisabled(): void
    {
        \SilverStripe\Core\Config\Config::modify()->set(
            MediaField::class,
            'enabled_types',
            ['image' => true, 'video' => false],
        );

        $parent = FieldList::create([
            DropdownField::create('MediaType', 'Type'),
            UploadField::create('MediaImage', 'Image'),
            TextField::create('MediaVideoFullURL', 'Video URL'),
        ]);

        $field = new MediaField($parent);

        self::assertInstanceOf(Wrapper::class, $field->getImageWrapper());
        self::assertNull($field->getVideoWrapper());
    }

    public function testConstructorOmitsImageWhenDisabled(): void
    {
        \SilverStripe\Core\Config\Config::modify()->set(
            MediaField::class,
            'enabled_types',
            ['image' => false, 'video' => true],
        );

        $parent = FieldList::create([
            DropdownField::create('MediaType', 'Type'),
            UploadField::create('MediaImage', 'Image'),
            TextField::create('MediaVideoFullURL', 'Video URL'),
        ]);

        $field = new MediaField($parent);

        self::assertNull($field->getImageWrapper());
        self::assertNull($field->getImageUploadField());
        self::assertInstanceOf(Wrapper::class, $field->getVideoWrapper());
    }

    public function testFieldHolderWiresDisplayLogicOnImageAndVideoWrappers(): void
    {
        $parent = FieldList::create([
            DropdownField::create('MediaType', 'Type'),
            UploadField::create('MediaImage', 'Image'),
            TextField::create('MediaVideoFullURL', 'Video URL'),
        ]);

        $field = new MediaField($parent);

        // FieldHolder() renders child templates which require a Form context.
        // Attaching a minimal Form propagates setForm() to all children recursively.
        $form = Form::create(null, 'TestForm', FieldList::create([$field]), FieldList::create());
        $field->setForm($form);

        $field->FieldHolder();

        $imageWrapper = $field->getImageWrapper();
        $videoWrapper = $field->getVideoWrapper();

        self::assertNotNull($imageWrapper);
        self::assertNotNull($videoWrapper);

        $imageCriteria = $imageWrapper->getDisplayLogicCriteria();
        $videoCriteria = $videoWrapper->getDisplayLogicCriteria();

        self::assertInstanceOf(
            Criteria::class,
            $imageCriteria,
            'Expected image wrapper to have a display-logic criterion after FieldHolder()',
        );
        self::assertInstanceOf(
            Criteria::class,
            $videoCriteria,
            'Expected video wrapper to have a display-logic criterion after FieldHolder()',
        );

        self::assertNotEmpty(
            $imageCriteria->getCriteria(),
            'Expected image wrapper display-logic criteria to be non-empty after FieldHolder()',
        );
        self::assertNotEmpty(
            $videoCriteria->getCriteria(),
            'Expected video wrapper display-logic criteria to be non-empty after FieldHolder()',
        );
    }
}
