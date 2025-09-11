<?php

namespace App\Enums;

enum CompaignParicipationStatusEnum : string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    case WITHDRAWN = 'withdrawn';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function labels(): array
    {
        return [
            self::PENDING->value => __('enums.compaign_participation_status.pending'),
            self::APPROVED->value => __('enums.compaign_participation_status.approved'),
            self::REJECTED->value => __('enums.compaign_participation_status.rejected'),
            self::WITHDRAWN->value => __('enums.compaign_participation_status.withdrawn'),
        ];
    }
    public function label(): string
    {
        return self::labels()[$this->value];
    }
}
