<?php

namespace Modules\Offer\Models;

use App\Enums\OfferStatusEnum;
use App\Enums\SaleTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Offer extends Model
{
    protected $fillable = [
        'discount_type',
        'discount_amount',
        'start_date',
        'end_date',
        'is_flash_sale',
        'has_stock_limit',
        'stock_limit',
        'is_active',
        'status',
        'offerable_id',
        'offerable_type',
        // 'discount_amount_minor',
        'currency_code',
        'currency_factor',
    ];

    /**
     * Get discount amount in minor units, computing from discount_amount if needed.
     */
    public function discountAmountMinorValue(int $defaultFactor = 100): ?int
    {
        if ($this->discount_amount_minor !== null) {
            return (int) $this->discount_amount_minor;
        }

        $factor = $this->currency_factor ?? $this->offerable?->store?->address?->zone?->city?->governorate?->country?->currency_factor ?? $defaultFactor;

        return $this->discount_amount !== null ? \App\Helpers\CurrencyHelper::toMinorUnits((float) $this->discount_amount, (int) $factor) : null;
    }
    protected $casts = [
        'discount_amount' => 'decimal:3',
        'status' => OfferStatusEnum::class,
        'discount_type' => SaleTypeEnum::class,
        'is_active' => 'boolean',
        'is_flash_sale' => 'boolean',
        'has_stock_limit' => 'boolean',
    ];
    public function offerable(): MorphTo
    {
        return $this->morphTo();
    }
    public function scopeFilter($query, $filters)
    {
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }
        if (isset($filters['discount_type'])) {
            $query->where('discount_type', $filters['discount_type']);
        }
        if (isset($filters['is_flash_sale'])) {
            $query->where('is_flash_sale', $filters['is_flash_sale']);
        }
        return $query;
    }
      protected static function booted()
    {
        $handleStatusChanges = function ($offer) {
            if ($offer->isDirty('is_active') && $offer->is_active) {
                $offer->status = OfferStatusEnum::ACTIVE;
            }
            if ($offer->isDirty('is_active') && !$offer->is_active) {
                $offer->status = OfferStatusEnum::INACTIVE;
            }
        };
        static::updating($handleStatusChanges);
    }
}
