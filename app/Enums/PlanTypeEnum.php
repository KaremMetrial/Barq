<?php

namespace App\Enums;

enum PlanTypeEnum: string
{
    case SUBSCRIPTION = 'subscription';
    case COMMISSION = 'commission';

    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
    public static function labels(): array
    {
        return [
            self::SUBSCRIPTION->value => __('enums.plan_type.subscription'),
            self::COMMISSION->value => __('enums.plan_type.commission'),
        ];
    }
    public static function label(string $value): string
    {
        return self::labels()[$value] ?? __('Unknown');
    }
}
