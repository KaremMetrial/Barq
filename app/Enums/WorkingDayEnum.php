<?php

namespace App\Enums;

enum WorkingDayEnum: int
{
    case SATURDAY = 6;  // Saturday mapped to 6 (because PHP's `dayOfWeek` uses 0-6, Saturday is 6)
    case SUNDAY = 0;    // Sunday mapped to 0
    case MONDAY = 1;    // Monday mapped to 1
    case TUESDAY = 2;   // Tuesday mapped to 2
    case WEDNESDAY = 3; // Wednesday mapped to 3
    case THURSDAY = 4;  // Thursday mapped to 4
    case FRIDAY = 5;    // Friday mapped to 5

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
    public static function labels(): array
    {
        return [
            self::SATURDAY->value  => __('enums.working_day.saturday'),
            self::SUNDAY->value    => __('enums.working_day.sunday'),
            self::MONDAY->value    => __('enums.working_day.monday'),
            self::TUESDAY->value   => __('enums.working_day.tuesday'),
            self::WEDNESDAY->value => __('enums.working_day.wednesday'),
            self::THURSDAY->value  => __('enums.working_day.thursday'),
            self::FRIDAY->value    => __('enums.working_day.friday'),
        ];
    }
    public static function label(int $value): string
    {
        return self::labels()[$value] ?? __('Unknown');
    }
}
