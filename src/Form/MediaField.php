<?php

namespace TheWebmen\MediaField\Form;

use Embed\Embed;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use UncleCheese\DisplayLogic\Forms\Wrapper;

class MediaField extends CompositeField {

    private static $media_types = [
        'image' => 'Image',
        'video' => 'Video'
    ];

    private $typeField;

    private $videoWrapper;
    private $imageWrapper;
    private $imageUploadField;

    /**
     * MediaField
     * @param FieldList $fields
     * @param string $typeField
     * @param string $imageField
     * @param string $videoField
     * @param string $imageFolder
     */
    public function __construct($fields, $typeField = 'MediaType', $imageField = 'MediaImage', $videoField = 'MediaVideo', $imageFolder = 'Media')
    {
        $this->typeField = $typeField;

        $fields->removeByName($typeField);
        $fields->removeByName($imageField);
        $fields->removeByName($videoField);

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

    public function FieldHolder($properties = array())
    {
        //Display logic
        $this->imageWrapper->displayIf($this->typeField)->isEqualTo('image');
        $this->videoWrapper->displayIf($this->typeField)->isEqualTo('video');

        return parent::FieldHolder($properties);
    }

    /**
     * @return Wrapper
     */
    public function getVideoWrapper()
    {
        return $this->videoWrapper;
    }

    /**
     * @return Wrapper
     */
    public function getImageWrapper()
    {
        return $this->imageWrapper;
    }

    /**
     * @return UploadField
     */
    public function getImageUploadField()
    {
        return $this->imageUploadField;
    }

    /**
     * @param $object
     * @param string $videoField
     * @param string $embedUrlField
     * @param string $embedTypeField
     */
    public static function saveEmbed($object, $videoField = 'MediaVideo', $embedUrlField = 'MediaVideoEmbedUrl', $embedTypeField = 'MediaVideoType')
    {
        if($object->$videoField && ($object->isChanged($videoField) || !$object->$embedUrlField)){
            $oEmbed = Embed::create($object->$videoField);
            $oEmbedClass = get_class($oEmbed);
            $iframeCode = $oEmbed->getCode();
            preg_match('/src="([^"]+)"/', $iframeCode, $match);
            $object->$embedUrlField = $match[1];
            $object->$embedTypeField = strpos($oEmbedClass, 'Youtube') !== false ? 'youtube' : 'vimeo';
        }
    }

}
