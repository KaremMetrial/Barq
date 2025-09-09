<?php

namespace App\Enums;

enum OptionInputTypeEnum: string
{
    case SINGLE = 'single';
    case MULTIPLE = 'multiple';

    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
    public static function labels(): array
    {
        return [
            self::SINGLE->value => __('enums.option_input_type.single'),
            self::MULTIPLE->value => __('enums.option_input_type.multiple'),
        ];
    }
    public static function label(string $value): string
    {
        return self::labels()[$value] ?? __('Unknown');
    }
}
