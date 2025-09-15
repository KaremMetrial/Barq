<?php

namespace App\Enums;

enum PaymentStatusEnum: string
{
    case UNPAID = "unpaid";
    case PAID = "paid";
    case PARTIALLY_PAID = "partially_paid";

    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }

    public static function labels(): array
    {
        return [
            self::UNPAID->value         => __('enums.payment_status.unpaid'),
            self::PAID->value           => __('enums.payment_status.paid'),
            self::PARTIALLY_PAID->value => __('enums.payment_status.partially_paid'),
        ];
    }

    public static function label(string $value): string
    {
        return self::labels()[$value] ?? __('Unknown');
    }

}
