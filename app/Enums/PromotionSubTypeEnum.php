<?php

namespace App\Enums;

enum PromotionSubTypeEnum: string
{
    case FREE_DELIVERY = 'free_delivery';
    case DISCOUNT_DELIVERY = 'discount_delivery';
    case FIXED_DELIVERY = 'fixed_delivery';
    case FIXED_PRICE = 'fixed_price';
    case PERCENTAGE_DISCOUNT = 'percentage_discount';
    case FIRST_ORDER = 'first_order';
    case BUNDLE = 'bundle';
    case BUY_ONE_GET_ONE = 'buy_one_get_one';

    public static function labels(): array
    {
        return [
            self::FREE_DELIVERY->value => __('enums.promotion_sub_type.free_delivery'),
            self::DISCOUNT_DELIVERY->value => __('enums.promotion_sub_type.discount_delivery'),
            self::FIXED_DELIVERY->value => __('enums.promotion_sub_type.fixed_delivery'),
            self::FIXED_PRICE->value => __('enums.promotion_sub_type.fixed_price'),
            self::PERCENTAGE_DISCOUNT->value => __('enums.promotion_sub_type.percentage_discount'),
            self::FIRST_ORDER->value => __('enums.promotion_sub_type.first_order'),
            self::BUNDLE->value => __('enums.promotion_sub_type.bundle'),
            self::BUY_ONE_GET_ONE->value => __('enums.promotion_sub_type.buy_one_get_one'),
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
