<?php

namespace App\Enums;

enum LoyaltyTrransactionTypeEnum: string
{
    case EARNED = 'earned';
    case REDEEMED = 'redeemed';
    case EXPIRED = 'expired';
    case ADJUSTED = 'adjusted';
    public static function labels():array
    {
        return [
            self::EARNED->value => __('enums.loyalty_transaction_type.earned'),
            self::REDEEMED->value => __('enums.loyalty_transaction_type.redeemed'),
            self::EXPIRED->value => __('enums.loyalty_transaction_type.expired'),
            self::ADJUSTED->value => __('enums.loyalty_transaction_type.adjusted'),
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
