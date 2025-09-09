<?php

namespace App\Enums;

enum ProductStatusEnum: string
{
    case PENDING = "pending";
    case ACTIVE = "active";
    case INACTIVE = "inactive";
    case REJECTED = "rejected";

    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
    public static function labels(): array
    {
        return [
            self::PENDING->value => __('enums.product_status.pending'),
            self::ACTIVE->value => __('enums.product_status.active'),
            self::INACTIVE->value => __('enums.product_status.inactive'),
            self::REJECTED->value => __('enums.product_status.rejected'),
        ];
    }
    public static function label(string $value): string
    {
        return self::labels()[$value] ?? __('Unknown');
    }
}
