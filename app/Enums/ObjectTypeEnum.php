<?php

namespace App\Enums;

enum ObjectTypeEnum : string
{
    case GENERAL = 'general';
    case PRODUCT = 'product';
    case STORE = 'store';
    case CATEGORY = 'category';

    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
    public static function labels(): array
    {
        return [
            self::GENERAL->value => __('enums.object_type.general'),
            self::PRODUCT->value => __('enums.object_type.product'),
            self::STORE->value => __('enums.object_type.store'),
            self::CATEGORY->value => __('enums.object_type.category'),
        ];
    }
    public static function label(string $value): string
    {
        return self::labels()[$value] ?? __('Unknown');
    }
}
