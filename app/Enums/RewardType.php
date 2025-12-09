<?php

namespace App\Enums;

enum RewardType: string
{
    case WALLET = "wallet";
    case COUPON = "coupon";
    case PRIZE = "prize";

    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
    public static function labels(): array
    {
        return [
            self::WALLET->value => __('enums.reward_type.wallet'),
            self::COUPON->value => __('enums.reward_type.coupon'),
            self::PRIZE->value => __('enums.reward_type.prize'),
        ];
    }
    public static function label(string $value): string
    {
        return self::labels()[$value] ?? __('Unknown');
    }
}
