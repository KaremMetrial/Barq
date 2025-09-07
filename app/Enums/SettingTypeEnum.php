<?php
namespace App\Enums;

enum SettingTypeEnum: string {
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
            self::STRING->value  => 'String',
            self::INTEGER->value => 'Integer',
            self::BOOLEAN->value => 'Boolean',
            self::FILE->value    => 'File',
        ];
    }

    public static function label(string $value): string
    {
        return self::labels()[$value] ?? 'Unknown';
    }
}
