<?php

namespace App\Enums;

enum AdStatusEnum: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case REJECTED = 'rejected';
   case PASUED = 'pasued';
    case APPROVED = 'approved';

    case EXPIRED = 'expired';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function labels(): array
    {
        return [
            self::PENDING->value => __('enums.ad_status.pending'),
            self::ACTIVE->value => __('enums.ad_status.active'),
            self::INACTIVE->value => __('enums.ad_status.inactive'),
            self::REJECTED->value => __('enums.ad_status.rejected'),
            self::PASUED->value => __('enums.ad_status.pasued'),
            self::APPROVED->value => __('enums.ad_status.approved'),
            self::EXPIRED->value => __('enums.ad_status.expired'),
        ];
    }

    public static function label(string $value): string
    {
        return self::labels()[$value] ?? __('Unknown');
    }
}
