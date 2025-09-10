<?php

namespace App\Enums;

enum CouponTypeEnum: string
{
    case FREE_DELIVERY = 'free_delivery';
    case REGULAR = 'regular';

    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
    public static function labels(): array
    {
        return [
            self::FREE_DELIVERY->value => __('enums.coupon_type.free_delivery'),
            self::REGULAR->value => __('enums.coupon_type.regular'),
        ];
    }
    public static function label(string $value): string
    {
        return self::labels()[$value] ?? __('Unknown');
    }
}
