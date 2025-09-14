<?php

namespace App\Enums;

enum ReportTypeEnum: string
{
    case DELIVERY_ISSUE = "delivery_issue";
    case PAYMENT_ISSUE = "payment_issue";
    case ORDER_ISSUE = "order_issue";
    case WORNG_ITEM_RECEIVED = "worng_item_received";
    case DAMAGED_ITEM_RECEIVED = "damaged_item_received";
    case CUTOMER_SERVICE_ISSUE = "customer_service_issue";
    case APP_BUG = "app_bug";
    case OTHER = "other";
    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }


    public static function labels(): array
    {
        return [
            self::DELIVERY_ISSUE->value => __('enums.report_type.delivery_issue'),
            self::PAYMENT_ISSUE->value => __('enums.report_type.payment_issue'),
            self::ORDER_ISSUE->value => __('enums.report_type.order_issue'),
            self::WORNG_ITEM_RECEIVED->value => __('enums.report_type.worng_item_received'),
            self::DAMAGED_ITEM_RECEIVED->value => __('enums.report_type.damaged_item_received'),
            self::CUTOMER_SERVICE_ISSUE->value => __('enums.report_type.customer_service_issue'),
            self::APP_BUG->value => __('enums.report_type.app_bug'),
            self::OTHER->value => __('enums.report_type.other'),
        ];
    }
    public static function label(string $value): string
    {
        return self::labels()[$value] ?? __('Unknown');
    }
}
