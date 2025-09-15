<?php

namespace App\Enums;

enum OrderTypeEnum: string
{
    case PICKUP = "pickup";
    case DELIVER = "deliver";
    case SERVICE = "service";
    case POS = "pos";
    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }

    public static function labels(): array
    {
        return [
            self::PICKUP->value  => __('enums.order_type.pickup'),
            self::DELIVER->value => __('enums.order_type.deliver'),
            self::SERVICE->value => __('enums.order_type.service'),
            self::POS->value     => __('enums.order_type.pos'),
        ];
    }

    public static function label(string $value): string
    {
        return self::labels()[$value] ?? __('Unknown');
    }

}
