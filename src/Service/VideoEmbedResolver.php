<?php

namespace WeDevelop\MediaField\Service;

use Embed\Embed;
use Embed\Extractor;
use Embed\Http\BlockedRequestException;
use Embed\Http\NetworkException;
use Psr\Log\LoggerInterface;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Validation\ValidationException;
use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\ORM\DataObject;

class VideoEmbedResolver
{
    use Injectable;

    /**
     * The `.mediafield` services are configured in `_config/embed.yml`: they keep embed/embed's
     * CurlClient, which pins the validated IP, unlike the framework's Guzzle-backed Embed.
     *
     * @var array<string, string>
     */
    private static array $dependencies = [
        'embed' => '%$' . Embed::class . '.mediafield',
        'logger' => '%$' . LoggerInterface::class,
    ];

    public Embed $embed;

    public LoggerInterface $logger;

    /**
     * @throws ValidationException when the video URL is refused or its site cannot be reached
     */
    public function resolve(
        DataObject $object,
        string $videoFullURLField = 'MediaVideoFullURL',
        string $videoEmbeddedURLField = 'MediaVideoEmbeddedURL',
        string $videoProviderField = 'MediaVideoProvider',
        string $videoEmbeddedNameField = 'MediaVideoEmbeddedName',
        string $videoEmbeddedDescriptionField = 'MediaVideoEmbeddedDescription',
        string $videoEmbeddedThumbnailField = 'MediaVideoEmbeddedThumbnail',
        string $videoEmbeddedCreatedField = 'MediaVideoEmbeddedCreated',
    ): void {
        if (!$object->$videoFullURLField || (!$object->isChanged($videoFullURLField) && $object->$videoEmbeddedURLField)) {
            return;
        }

        $embedData = $this->fetch($object, $videoFullURLField);
        $iframeCode = (string)$embedData->code;
        preg_match('/src="([^"]+)"/', $iframeCode, $match);

        if (!isset($match[1])) {
            return;
        }

        $object->$videoEmbeddedURLField = $match[1];
        $object->$videoProviderField = (string)$embedData->providerName;
        $object->$videoEmbeddedNameField = (string)$embedData->title;
        $object->$videoEmbeddedDescriptionField = (string)$embedData->description;

        if ($embedData->providerName === 'Vimeo') {
            $object->$videoEmbeddedThumbnailField = $embedData->getOEmbed()->get('thumbnail_url');
            $object->$videoEmbeddedCreatedField = $embedData->getOEmbed()->get('upload_date') ?? '';
        } else {
            $object->$videoEmbeddedThumbnailField = (string)$embedData->image;
            $object->$videoEmbeddedCreatedField = $embedData->publishedTime?->format(\DateTimeInterface::ATOM);
        }
    }

    private function fetch(DataObject $object, string $videoFullURLField): Extractor
    {
        try {
            return $this->embed->get((string)$object->$videoFullURLField);
        } catch (BlockedRequestException $e) {
            $this->logger->warning('Refused to fetch video details for a blocked address', [
                'reason' => $e->getReason(),
                'class' => $object::class,
                'id' => $object->ID,
            ]);

            throw $this->fieldError($videoFullURLField, _t(
                self::class . '.BLOCKED',
                "This address can't be used for a video. Enter the full public address of the video, starting with https://."
            ));
        } catch (NetworkException) {
            throw $this->fieldError($videoFullURLField, _t(
                self::class . '.UNREACHABLE',
                'The video site could not be reached to fetch the video details. Try saving again in a moment.'
            ));
        }
    }

    private function fieldError(string $fieldName, string $message): ValidationException
    {
        return ValidationException::create(ValidationResult::create()->addFieldError($fieldName, $message));
    }
}
