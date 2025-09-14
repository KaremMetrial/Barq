<?php

namespace App\Enums;

enum AddressTypeEnum : string
{
    case WORK = 'work';
    case HOME = 'home';
    case OTHER = 'other';

    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }

    public static function labels(): array
    {
        return [
            self::WORK->value => __('enums.address_type.work'),
            self::HOME->value => __('enums.address_type.home'),
            self::OTHER->value => __('enums.address_type.other'),
        ];
    }
    public static function label(string $value): string
    {
        return self::labels()[$value] ?? __('Unknown');
    }
}
