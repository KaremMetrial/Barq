<?php

namespace App\Enums;

enum WorkingDayEnum: int
{
    case SATURDAY = 1;
    case SUNDAY = 2;
    case MONDAY = 3;
    case TUESDAY = 4;
    case WEDNESDAY = 5;
    case THURSDAY = 6;
    case FRIDAY = 7;

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
