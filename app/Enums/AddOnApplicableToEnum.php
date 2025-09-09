<?php

namespace App\Enums;

enum AddOnApplicableToEnum : string
{
    case PRODUCT = 'product';
    case DELIVERY = 'delivery';
    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
    public static function labels(): array
    {
        return [
            self::PRODUCT->value => __('enums.add_on_applicable_to.product'),
            self::DELIVERY->value => __('enums.add_on_applicable_to.delivery'),
        ];
    }
    public static function label(string $value): string
    {
        return self::labels()[$value] ?? __('Unknown');
    }
}
