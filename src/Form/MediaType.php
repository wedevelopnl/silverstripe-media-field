<?php

namespace WeDevelop\MediaField\Form;

enum MediaType: string
{
    case Image = 'image';
    case Video = 'video';

    public function label(): string
    {
        return match ($this) {
            self::Image => 'Image',
            self::Video => 'Video',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function toDropdownSource(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(static fn (self $case): string => $case->label(), self::cases())
        );
    }
}
