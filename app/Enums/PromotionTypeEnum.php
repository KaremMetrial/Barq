<?php

namespace App\Enums;

enum PromotionTypeEnum: string
{
    case DELIVERY = 'delivery';
    case PRODUCT = 'product';
    case ORDER = 'order';
    case USER = 'user';

    public static function labels(): array
    {
        return [
            self::DELIVERY->value => __('enums.promotion_type.delivery'),
            self::PRODUCT->value => __('enums.promotion_type.product'),
            self::ORDER->value => __('enums.promotion_type.order'),
            self::USER->value => __('enums.promotion_type.user'),
        ];
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function label(string $value): string
    {
        return self::labels()[$value] ?? __('Unknown');
    }
}
