<?php

namespace WeDevelop\MediaField\Tests\Stub;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class MediaFieldDataObjectStub extends DataObject implements TestOnly
{
    /** @var array<string, string> */
    private static array $db = [
        'MediaVideoFullURL' => 'Varchar(255)',
        'MediaVideoEmbeddedURL' => 'Varchar(255)',
        'MediaVideoProvider' => 'Varchar(64)',
        'MediaVideoEmbeddedName' => 'Varchar(255)',
        'MediaVideoEmbeddedDescription' => 'Text',
        'MediaVideoEmbeddedThumbnail' => 'Varchar(255)',
        'MediaVideoEmbeddedCreated' => 'Varchar(64)',
    ];

    private static string $table_name = 'MediaFieldDataObjectStub';
}
