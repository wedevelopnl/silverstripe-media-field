<?php

namespace WeDevelop\MediaField\Form;

use Embed\Embed;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBHTMLText;
use UncleCheese\DisplayLogic\Forms\Wrapper;

class MediaField extends CompositeField
{
    use Configurable;

    /**
     * Which media types are enabled
     * @config
     * @var array<string, bool>
     */
    private static array $enabled_types = [
        'image' => true,
        'video' => true,
    ];

    private ?Wrapper $videoWrapper = null;

    private ?Wrapper $imageWrapper = null;

    private ?UploadField $imageUploadField = null;

    private string $typeField;

    public function __construct(FieldList $fields, string $mediaUploadFolder = 'MediaUploads', string $typeField = 'MediaType', string $imageField = 'MediaImage', string $videoField = 'MediaVideoFullURL')
    {
        $this->typeField = $typeField;
        $enabledTypes = $this->config()->get('enabled_types');

        $fields->removeByName([
            $typeField,
            $imageField,
            $videoField,
        ]);

        $children = [];

        // Type dropdown (filtered by enabled types)
        $dropdownSource = [];
        foreach (MediaType::cases() as $type) {
            if ($enabledTypes[$type->value] ?? true) {
                $dropdownSource[$type->value] = $type->label();
            }
        }
        $children[] = DropdownField::create($typeField, 'Type', $dropdownSource);

        // Image (only if enabled)
        if ($enabledTypes[MediaType::Image->value] ?? true) {
            $this->imageWrapper = Wrapper::create(
                $this->imageUploadField = UploadField::create($imageField, 'Image')->setFolderName($mediaUploadFolder)
            );
            $children[] = $this->imageWrapper;
        }

        // Video (only if enabled)
        if ($enabledTypes[MediaType::Video->value] ?? true) {
            $this->videoWrapper = Wrapper::create(
                TextField::create($videoField)
            );
            $children[] = $this->videoWrapper;
        }

        parent::__construct($children);
    }

    /** @param array<string, mixed> $properties */
    public function FieldHolder($properties = []): DBHTMLText
    {
        $this->imageWrapper?->displayIf($this->typeField)->isEqualTo(MediaType::Image->value);
        $this->videoWrapper?->displayIf($this->typeField)->isEqualTo(MediaType::Video->value);

        return parent::FieldHolder($properties);
    }

    public function getVideoWrapper(): ?Wrapper
    {
        return $this->videoWrapper;
    }

    public function getImageWrapper(): ?Wrapper
    {
        return $this->imageWrapper;
    }

    public function getImageUploadField(): ?UploadField
    {
        return $this->imageUploadField;
    }

    public static function saveEmbed(
        DataObject $object,
        Embed $embed,
        string $videoFullURLField = 'MediaVideoFullURL',
        string $videoEmbeddedURLField = 'MediaVideoEmbeddedURL',
        string $videoProviderField = 'MediaVideoProvider',
        string $videoEmbeddedNameField = 'MediaVideoEmbeddedName',
        string $videoEmbeddedDescriptionField = 'MediaVideoEmbeddedDescription',
        string $videoEmbeddedThumbnailField = 'MediaVideoEmbeddedThumbnail',
        string $videoEmbeddedCreatedField = 'MediaVideoEmbeddedCreated',
    ): void {
        if ($object->$videoFullURLField && ($object->isChanged($videoFullURLField) || !$object->$videoEmbeddedURLField)) {
            $embedData = $embed->get($object->$videoFullURLField);
            $iframeCode = (string) $embedData->code;
            preg_match('/src="([^"]+)"/', $iframeCode, $match);

            if (!isset($match[1])) {
                return;
            }

            $object->$videoEmbeddedURLField = $match[1];
            $object->$videoProviderField = (string) $embedData->providerName;
            $object->$videoEmbeddedNameField = (string) $embedData->title;
            $object->$videoEmbeddedDescriptionField = (string) $embedData->description;

            if ($embedData->providerName === 'Vimeo') {
                $object->$videoEmbeddedThumbnailField = $embedData->getOEmbed()->get('thumbnail_url');
                $object->$videoEmbeddedCreatedField = $embedData->getOEmbed()->get('upload_date') ?? '';
            } else {
                $object->$videoEmbeddedThumbnailField = (string) $embedData->image;
                $object->$videoEmbeddedCreatedField = $embedData->publishedTime?->format(\DateTimeInterface::ATOM);
            }
        }
    }
}
