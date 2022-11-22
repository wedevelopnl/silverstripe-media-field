<?php

namespace WeDevelop\MediaField\Form;

use Embed\Embed;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\TextField;
use UncleCheese\DisplayLogic\Forms\Wrapper;
use SilverStripe\ORM\FieldType\DBHTMLText;

class MediaField extends CompositeField
{
    /** @config */
    private $typeField;

    /** @config */
    private $videoWrapper;

    /** @config */
    private $imageWrapper;

    /** @config */
    private $imageUploadField;

    /** @config */
    private static array $media_types = [
        'image' => 'Image',
        'video' => 'Video',
    ];

    public function __construct($fields, $typeField = 'MediaType', $imageField = 'MediaImage', $videoField = 'MediaVideo', $imageFolder = 'Media')
    {
        $this->typeField = $typeField;

        $fields->removeByName([
            $typeField,
            $imageField,
            $videoField,
        ]);

        $children = [];

        //Type
        $children[] = DropdownField::create($typeField, 'Type', self::$media_types);

        //Image
        $this->imageWrapper = Wrapper::create(
            $this->imageUploadField = UploadField::create($imageField, 'Image')->setFolderName($imageFolder)
        );
        $children[] = $this->imageWrapper;

        //Video
        $this->videoWrapper = Wrapper::create(
            TextField::create($videoField, 'Video')
        );
        $children[] = $this->videoWrapper;

        parent::__construct($children);
    }

    public function FieldHolder($properties = []): DBHTMLText
    {
        //Display logic
        $this->imageWrapper->displayIf($this->typeField)->isEqualTo('image');
        $this->videoWrapper->displayIf($this->typeField)->isEqualTo('video');

        return parent::FieldHolder($properties);
    }

    public function getVideoWrapper(): Wrapper
    {
        return $this->videoWrapper;
    }

    public function getImageWrapper(): Wrapper
    {
        return $this->imageWrapper;
    }

    public function getImageUploadField(): UploadField
    {
        return $this->imageUploadField;
    }

    public static function saveEmbed(
        mixed $object,
        string $videoField = 'MediaVideo',
        string $embedUrlField = 'MediaVideoEmbedUrl',
        string $embedTypeField = 'MediaVideoType',
        string $embedName = 'MediaVideoEmbedName',
        string $embedDescription = 'MediaVideoEmbedDescription',
        string $embedImage = 'MediaVideoEmbedImage',
        string $embedCreated = 'MediaVideoEmbedCreated',
    ): void {
        if ($object->$videoField && ($object->isChanged($videoField) || !$object->$embedUrlField)) {
            $embed = (new Embed())->get($object->$videoField);
            $iframeCode = (string)$embed->code;
            preg_match('/src="([^"]+)"/', $iframeCode, $match);

            if (!isset($match[1])) {
                return;
            }

            $object->$embedUrlField = $match[1];
            $object->$embedTypeField = (string)$embed->providerName === 'YouTube' ? 'youtube' : 'vimeo';
            $object->$embedName = (string)$embed->title;
            $object->$embedDescription = (string)$embed->description;
            if ($embed->providerName === 'vimeo') {
                $object->$embedImage = $embed->getOEmbed()->get('thumbnail_url');
            } else {
                $object->$embedImage = (string)$embed->image;
            }
            $object->$embedCreated = $embed->publishedTime?->format(\DateTime::ISO8601);
        }
    }
}
