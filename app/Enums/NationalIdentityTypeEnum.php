<?php

namespace App\Enums;

enum NationalIdentityTypeEnum: string
{
    case NATTIONAL_ID = "national_id";
    case DRIVING_LICENSE = "driving_license";
    case PASSPORT = "passport";
    case OTHER = "other";

    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
    public static function labels(): array
    {
        return [
            self::NATTIONAL_ID->value => __('enums.national_identity_type.national_id'),
            self::DRIVING_LICENSE->value => __('enums.national_identity_type.driving_license'),
            self::PASSPORT->value => __('enums.national_identity_type.passport'),
            self::OTHER->value => __('enums.national_identity_type.other'),
        ];
    }
    public static function label(string $value): string
    {
        return self::labels()[$value] ?? __('Unknown');
    }
}
