<?php

namespace App\Enums;

enum SectionTypeEnum: string
{
    case RESTAURANT = 'restaurant';
    case PHARMACY = 'pharmacy';
    case REGULAR_STORE = 'regular_store';
    case OTHER = 'other';

    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }

    public static function labels(): array
    {
        return [
            self::RESTAURANT->value => __('enums.section_type.restaurant'),
            self::PHARMACY->value => __('enums.section_type.pharmacy'),
            self::REGULAR_STORE->value => __('enums.section_type.regular_store'),
            self::OTHER->value => __('enums.section_type.other'),
        ];
    }

    public static function label(string $value): string
    {
        return self::labels()[$value] ?? __('Unknown');

    }
}
