<?php

namespace App\Enums;

enum MessageTypeEnum: string
{
    case TEXT = "text";
    case IMAGE = "image";
    case VIDEO = "video";
    case AUDIO = "audio";

    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
    public static function labels(): array
    {
        return [
            self::TEXT->value  => __('enums.message_type.text'),
            self::IMAGE->value => __('enums.message_type.image'),
            self::VIDEO->value => __('enums.message_type.video'),
            self::AUDIO->value => __('enums.message_type.audio'),
        ];
    }

    public static function label(string $value): string
    {
        return self::labels()[$value] ?? __('Unknown');
    }

}
