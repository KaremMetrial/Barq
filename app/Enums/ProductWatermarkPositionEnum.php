<?php

namespace App\Enums;

enum ProductWatermarkPositionEnum: string
{
    case TOP_LEFT = "top_left";
    case TOP_RIGHT = "top_right";
    case BOTTOM_LEFT = "bottom_left";
    case BOTTOM_RIGHT = "bottom_right";
    case CENTER = "center";

    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
    public static function labels(): array
    {
        return [
            self::TOP_LEFT->value      => __('enums.product_watermark_position.top_left'),
            self::TOP_RIGHT->value     => __('enums.product_watermark_position.top_right'),
            self::BOTTOM_LEFT->value   => __('enums.product_watermark_position.bottom_left'),
            self::BOTTOM_RIGHT->value  => __('enums.product_watermark_position.bottom_right'),
            self::CENTER->value        => __('enums.product_watermark_position.center'),
        ];
    }
    public static function label(string $value): string
    {
        return self::labels()[$value] ?? __('Unknown');
    }
}
