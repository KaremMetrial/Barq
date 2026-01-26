<?php

namespace App\Enums;

enum PromotionTargetTypeEnum: string
{
    case STORE = 'store';
    case CATEGORY = 'category';
    case PRODUCT = 'product';
    case USER_GROUP = 'user_group';
    case SECTION = 'section';
    public static function labels(): array
    {
        return [
            self::STORE->value => __('enums.promotion_target_type.store'),
            self::CATEGORY->value => __('enums.promotion_target_type.category'),
            self::PRODUCT->value => __('enums.promotion_target_type.product'),
            self::USER_GROUP->value => __('enums.promotion_target_type.user_group'),
            self::SECTION->value => __('enums.promotion_target_type.section'),
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
