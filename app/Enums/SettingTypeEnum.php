<?php

namespace App\Enums;

enum SettingTypeEnum: string
{
    case STRING  = 'string';
    case INTEGER = 'integer';
    case BOOLEAN = 'boolean';
    case FILE    = 'file';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function labels(): array
    {
        return [
            self::STRING->value  => __('enums.setting_type.string'),
            self::INTEGER->value => __('enums.setting_type.integer'),
            self::BOOLEAN->value => __('enums.setting_type.boolean'),
            self::FILE->value    => __('enums.setting_type.file'),
        ];
    }

    public static function label(string $value): string
    {
        return self::labels()[$value] ?? __('Unknown');
    }
}
