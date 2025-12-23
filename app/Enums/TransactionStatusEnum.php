<?php

namespace App\Enums;

enum TransactionStatusEnum: string
{
    case PENDING = 'pending';
    case SUCCESS = 'success';
    case FAILED = 'failed';
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function label(string $value): string
    {
        return self::labels()[$value] ?? __('Unknown');
    }

    public static function labels(): array
    {
        return [
            self::PENDING->value => __('enums.transaction_status.pending'),
            self::SUCCESS->value => __('enums.transaction_status.success'),
            self::FAILED->value => __('enums.transaction_status.failed'),
        ];
    }

}
