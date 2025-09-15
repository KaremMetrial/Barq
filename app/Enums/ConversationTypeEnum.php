<?php

namespace App\Enums;

enum ConversationTypeEnum: string
{
    case SUPPORT = "support";
    case DELIVERY = "delivery";
    case INQUIRY = "inquiry";

    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
    public static function labels(): array
    {
        return [
            self::SUPPORT->value => __('enums.conversation_type.support'),
            self::DELIVERY->value => __('enums.conversation_type.delivery'),
            self::INQUIRY->value => __('enums.conversation_type.inquiry'),
        ];
    }

    public static function label(string $value): string
    {
        return self::labels()[$value] ?? __('Unknown');
    }
}
