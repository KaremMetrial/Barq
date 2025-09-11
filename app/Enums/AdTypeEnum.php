<?php

namespace App\Enums;

enum AdTypeEnum : string
{
    case STANDARD = 'standard';
    case VIDEO = 'video';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
    public static function labels(): array
    {
        return [
            self::STANDARD->value => __('enums.ad_type.standard'),
            self::VIDEO->value => __('enums.ad_type.video'),
        ];
    }

    public static function label(string $value): string
    {
        return self::labels()[$value] ?? __('Unknown');
    }
}
