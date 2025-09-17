<?php

namespace App\Enums;

enum UnitTypeEnum: string
{
    case WEIGHT = "weight";
    case VOLUME = "volume";
    case LENGTH = "length";
    case COUNT  =  "count";

    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
    public static function labels(): array
    {
        return [
            self::WEIGHT->value => __('enums.unit_type.weight'),
            self::VOLUME->value => __('enums.unit_type.volume'),
            self::LENGTH->value => __('enums.unit_type.length'),
            self::COUNT->value => __('enums.unit_type.count'),
        ];
    }
    public static function label(string $value): string
    {
        return self::labels()[$value] ?? __('Unknown');
    }
}
