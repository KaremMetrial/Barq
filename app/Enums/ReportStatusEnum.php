<?php

namespace App\Enums;

enum ReportStatusEnum: string
{
    case PENDING = "pending";
    case PROCESSING = "processing";
    case RESOLVED = "resolved";
    case CLOSED = "closed";

    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
    public static function labels(): array
    {
        return [
            self::PENDING->value => __('enums.report_status.pending'),
            self::PROCESSING->value => __('enums.report_status.processing'),
            self::RESOLVED->value => __('enums.report_status.resolved'),
            self::CLOSED->value => __('enums.report_status.closed'),
        ];
    }
    public static function label(string $value): string
    {
        return self::labels()[$value] ?? __('Unknown');
    }
}
