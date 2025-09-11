<?php

namespace App\Enums;

enum SubscriptionStatusEnum: string
{
    case ACTIVE = "active";
    case INACTIVE = "inactive";
    case CANCELLED = "cancelled";
    case EXPIRED = "expired";
    case TRIAL = "trial";
    case PENDING = "pending";

    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }

    public static function labels(): array
    {
        return [
            self::ACTIVE => __('enums.subscription_status.active'),
            self::INACTIVE => __('enums.subscription_status.inactive'),
            self::CANCELLED => __('enums.subscription_status.cancelled'),
            self::EXPIRED => __('enums.subscription_status.expired'),
            self::TRIAL => __('enums.subscription_status.trial'),
            self::PENDING => __('enums.subscription_status.pending'),
        ];
    }
    public static function label(string $value): string
    {
        return self::labels()[$value] ?? __('Unknown');
    }
}
