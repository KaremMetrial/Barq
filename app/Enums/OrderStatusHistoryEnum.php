<?php

namespace App\Enums;

enum OrderStatusHistoryEnum: string
{
    case PENDING = "pending";
    case CONFIRMED = "confirmed";
    case PROCESSING = "processing";
    case ON_THE_WAY = "on_the_way";
    case READY_FOR_DELIVERY = "ready_for_delivery";
    case DELIVERED = "delivered";
    case CANCELLED = "cancelled";
    case FAILED = "failed";
     public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }

    public static function labels(): array
    {
        return [
            self::PENDING->value => __('enums.order_status_history.pending'),
            self::CONFIRMED->value => __('enums.order_status_history.confirmed'),
            self::PROCESSING->value => __('enums.order_status_history.processing'),
            self::ON_THE_WAY->value => __('enums.order_status_history.on_the_way'),
            self::READY_FOR_DELIVERY->value => __('enums.order_status_history.ready_for_delivery'),
            self::DELIVERED->value => __('enums.order_status_history.delivered'),
            self::CANCELLED->value => __('enums.order_status_history.cancelled'),
            self::FAILED->value => __('enums.order_status_history.failed')
        ];
    }

    public static function label(string $value): string
    {
        return self::labels()[$value] ?? __('Unknown');
    }

}
