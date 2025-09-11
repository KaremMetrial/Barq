<?php

namespace App\Enums;

enum PlanBillingCycleEnum: string
{
    case MONTHLY = 'monthly';
    case YEARLY = 'yearly';

    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }

    public static function labels(): array
    {
        return [
            self::MONTHLY->value => __('enums.plan_billing_cycle.monthly'),
            self::YEARLY->value => __('enums.plan_billing_cycle.yearly'),
        ];
    }
    public static function label(string $value): string
    {
        return self::labels()[$value] ?? __('Unknown');
    }
}
