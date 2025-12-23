<?php

namespace App\Enums;

enum TransactionType: string
{
    case PAY = 'pay';
    case REFUND = 'refund';
    case COMMISSION = 'commission';
    case WITHDRAWAL = 'withdrawal';
    case DEPOSIT = 'deposit';
    case EARNING = 'earning';
    case ADJUSTMENT = 'adjustment';
    case INCREMENT = 'increment';
    case DECREMENT = 'decrement';

    public static function labels(): array
    {
        return [
            self::PAY->value => __('enums.transaction_type.pay'),
            self::REFUND->value => __('enums.transaction_type.refund'),
            self::COMMISSION->value => __('enums.transaction_type.commission'),
            self::WITHDRAWAL->value => __('enums.transaction_type.withdrawal'),
            self::DEPOSIT->value => __('enums.transaction_type.deposit'),
            self::EARNING->value => __('enums.transaction_type.earning'),
            self::ADJUSTMENT->value => __('enums.transaction_type.adjustment'),
            self::INCREMENT->value => __('enums.transaction_type.increment'),
            self::DECREMENT->value => __('enums.transaction_type.decrement'),
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
