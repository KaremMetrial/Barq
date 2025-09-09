<?php

namespace App\Enums;

enum SaleTypeEnum: string
{
    case PERCENTAGE = "percentage";
    case FIXED = "fixed";

    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
    public static function labels(): array
    {
        return [
            self::PERCENTAGE->value => __('enums.sale_type.percentage'),
            self::FIXED->value => __('enums.sale_type.fixed'),
        ];
    }
    public static function label(string $value): string
    {
        return self::labels()[$value] ?? __('Unknown');
    }
}
