<?php

namespace App\Enums;

enum CouierAvaliableStatusEnum: string
{
    case AVAILABLE = "available";
    case BUSY = "busy";
    case OFF = "off";

    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
    public static function labels(): array
    {
        return [
            self::AVAILABLE->value => __('enums.couier_avaliable_status.available'),
            self::BUSY->value => __('enums.couier_avaliable_status.busy'),
            self::OFF->value => __('enums.couier_avaliable_status.off'),
        ];
    }

    public static function label(string $value): string
    {
        return self::labels()[$value] ?? __('Unknown');
    }
}
