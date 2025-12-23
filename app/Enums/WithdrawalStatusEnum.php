<?php

namespace App\Enums;

enum WithdrawalStatusEnum: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';

    public static function values()
    {
        return array_column(self::cases(), 'value');
    }
    public static function labels()
    {
        return [
            self::PENDING => __('enums.withdrawal_status.pending'),
            self::APPROVED => __('enums.withdrawal_status.approved'),
            self::REJECTED => __('enums.withdrawal_status.rejected'),
            self::PROCESSING => __('enums.withdrawal_status.processing'),
            self::COMPLETED => __('enums.withdrawal_status.completed'),
            self::FAILED => __('enums.withdrawal_status.failed'),
        ];
    }
    public static function label(int $value)
    {
        return self::labels()[$value] ?? 'Unknown';
    }
}
