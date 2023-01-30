<?php

namespace WeDevelop\MediaField\Form;

use Embed\Embed;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use UncleCheese\DisplayLogic\Extensions\DisplayLogic;
use UncleCheese\DisplayLogic\Forms\Wrapper;
use SilverStripe\ORM\FieldType\DBHTMLText;

class MediaField extends CompositeField
{

    private $videoWrapper;

    private $imageWrapper;

    private $imageUploadField;

    private $typeField;

    private static array $media_types = [
        'image' => 'Image',
        'video' => 'Video',
    ];

    public function __construct(FieldList $fields, $mediaUploadFolder = 'MediaUploads', $typeField = 'MediaType', $imageField = 'MediaImage', $videoField = 'MediaVideoFullURL')
    {
        $this->typeField = $typeField;

        $fields->removeByName([
            $typeField,
            $imageField,
            $videoField,
        ]);

        $children = [];

        // Type
        $children[] = DropdownField::create($typeField, 'Type', self::$media_types);

        // Image
        $this->imageWrapper = Wrapper::create(
            $this->imageUploadField = UploadField::create($imageField, 'Image')->setFolderName($mediaUploadFolder)
        );

        // Video
        $this->videoWrapper = Wrapper::create(
            TextField::create($videoField)
        );

        array_push($children, $this->imageWrapper, $this->videoWrapper);

        parent::__construct($children);
    }

    public function FieldHolder($properties = []): DBHTMLText
    {
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
        string $videoFullURLField = 'MediaVideoFullURL',
        string $videoEmbeddedURLField = 'MediaVideoEmbeddedURL',
        string $videoProviderField = 'MediaVideoProvider',
        string $videoEmbeddedNameField = 'MediaVideoEmbeddedName',
        string $videoEmbeddedDescriptionField = 'MediaVideoEmbeddedDescription',
        string $videoEmbeddedThumbnailField = 'MediaVideoEmbeddedThumbnail',
        string $videoEmbeddedCreatedField = 'MediaVideoEmbeddedCreated',
    ): void {
        if ($object->$videoFullURLField && ($object->isChanged($videoFullURLField) || !$object->$videoEmbeddedURLField)) {
            $embed = (new Embed())->get($object->$videoFullURLField);
            $iframeCode = (string)$embed->code;
            preg_match('/src="([^"]+)"/', $iframeCode, $match);

            if (!isset($match[1])) {
                return;
            }

            $object->$videoEmbeddedURLField = $match[1];
            $object->$videoProviderField = (string)$embed->providerName;
            $object->$videoEmbeddedNameField = (string)$embed->title;
            $object->$videoEmbeddedDescriptionField = (string)$embed->description;

            if ($embed->providerName === 'Vimeo') {
                $object->$videoEmbeddedThumbnailField = $embed->getOEmbed()->get('thumbnail_url');
                $object->$videoEmbeddedCreatedField = $embed->getOEmbed()->get('upload_date') ?? '';
            } else {
                $object->$videoEmbeddedThumbnailField = (string)$embed->image;
                $object->$videoEmbeddedCreatedField = $embed->publishedTime?->format(\DateTime::ISO8601);
            }
        }
    }
}
