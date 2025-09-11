<?php

namespace App\Enums;

enum UserStatusEnum : string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case BLOCKED = 'blocked';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function labels(): array
    {
        return [
            self::PENDING->value => __('enums.user_status.pending'),
            self::ACTIVE->value => __('enums.user_status.active'),
            self::INACTIVE->value => __('enums.user_status.inactive'),
            self::BLOCKED->value => __('enums.user_status.blocked'),
        ];
    }

    public static function label(string $value): string
    {
        return self::labels()[$value] ?? __('Unknown');
    }
}
