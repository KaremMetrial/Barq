<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case PROCESSING = 'processing';
    case READY_FOR_DELIVERY = 'ready_for_delivery';
    case ON_THE_WAY = 'on_the_way';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';
    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
    public static function labels(): array
    {
        return [
            self::PENDING->value            => __('enums.order_status.pending'),
            self::CONFIRMED->value          => __('enums.order_status.confirmed'),
            self::PROCESSING->value         => __('enums.order_status.processing'),
            self::READY_FOR_DELIVERY->value => __('enums.order_status.ready_for_delivery'),
            self::ON_THE_WAY->value         => __('enums.order_status.on_the_way'),
            self::DELIVERED->value          => __('enums.order_status.delivered'),
            self::CANCELLED->value          => __('enums.order_status.cancelled'),
        ];
    }
    public static function label(string $value): string
    {
        return self::labels()[$value] ?? __('Unknown');
    }
}
