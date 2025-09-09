<?php

namespace App\Enums;

enum StoreStatusEnum : string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function labels(): array
    {
        return [
            self::PENDING->value => __('enums.store_status.pending'),
            self::APPROVED->value => __('enums.store_status.approved'),
            self::REJECTED->value => __('enums.store_status.rejected'),
        ];
    }

    public static function label(string $value): string
    {
        return self::labels()[$value] ?? __('Unknown');
    }
}
