<?php

namespace App\Enums;

enum OfferStatusEnum: string
{
    case PENDING = "pending";
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case EXPIRED = 'expired';
    case REJECTED = 'rejected';
    case PASUED = 'pasued';
    case APPROVED = 'approved';

    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
    public static function labels(): array
    {
        return [
            self::PENDING->value => __('enums.offer_status.pending'),
            self::ACTIVE->value => __('enums.offer_status.active'),
            self::INACTIVE->value => __('enums.offer_status.inactive'),
            self::EXPIRED->value => __('enums.offer_status.expired'),
            self::REJECTED->value => __('enums.offer_status.rejected'),
            self::PASUED->value => __('enums.offer_status.pasued'),
            self::APPROVED->value => __('enums.offer_status.approved'),
        ];
    }
    public static function label(string $value)
    {
        return self::labels()[$value] ?? __('Unknown');
    }
}
