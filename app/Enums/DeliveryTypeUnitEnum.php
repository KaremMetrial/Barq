<?php

namespace App\Enums;

enum DeliveryTypeUnitEnum: string
{
    case MINUTE = "minute";
    case HOUR = "HOUR";
    case DAY = "day";
    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
    public static function labels(): array
    {
        return [
            self::MINUTE->value => __('enums.delivery_type_unit.minute'),
            self::HOUR->value   => __('enums.delivery_type_unit.hour'),
            self::DAY->value    => __('enums.delivery_type_unit.day'),
        ];
    }

    public static function label(string $value): string
    {
        return self::labels()[$value] ?? __('Unknown');
    }
}
