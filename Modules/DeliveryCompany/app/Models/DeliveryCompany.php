<?php

namespace Modules\DeliveryCompany\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Astrotomic\Translatable\Translatable;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;

class DeliveryCompany extends Model implements TranslatableContract
{
    use Translatable;

    public $translatedAttributes = ['name', 'description'];

    protected $fillable = [
        'email',
        'phone',
        'license_number',
        'tax_number',
        'commission_rate',
        'is_active',
        'operating_countries',
        'contact_person',
        'logo',
        'cover_image',
        'bank_account_details',
        'service_areas',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'commission_rate' => 'decimal:2',
        'operating_countries' => 'array',
        'bank_account_details' => 'array',
        'service_areas' => 'array',
    ];

    /**
     * Get the couriers associated with this delivery company.
     */
    public function couriers(): HasMany
    {
        return $this->hasMany(\Modules\Couier\Models\Couier::class, 'delivery_company_id');
    }

    /**
     * Get the stores that use this delivery company.
     */
    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(\Modules\Store\Models\Store::class, 'delivery_company_store')
                    ->withPivot('commission_rate', 'is_preferred', 'contract_start_date', 'contract_end_date')
                    ->withTimestamps();
    }

    /**
     * Get the orders handled by this delivery company.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(\Modules\Order\Models\Order::class, 'delivery_company_id');
    }

    /**
     * Get the working days for this delivery company.
     * Note: Using polymorphic relationship to reuse existing working_days table.
     */
    public function workingDays(): HasMany
    {
        return $this->hasMany(\Modules\WorkingDay\Models\WorkingDay::class, 'store_id');
    }

    /**
     * Get the shipping prices for this delivery company.
     * Note: Using polymorphic relationship to reuse existing shipping_prices table.
     */
    public function shippingPrices(): HasMany
    {
        return $this->hasMany(\App\Models\ShippingPrice::class, 'zone_id');
    }

    /**
     * Get the address for this delivery company.
     */
    public function address()
    {
        return $this->morphOne(\Modules\Address\Models\Address::class, 'addressable');
    }

    /**
     * Get the balance for this delivery company.
     */
    public function balance()
    {
        return $this->morphOne(\Modules\Balance\Models\Balance::class, 'balanceable');
    }

    /**
     * Get the attachments for this delivery company.
     */
    public function attachments()
    {
        return $this->morphMany(\Modules\Attachment\Models\Attachment::class, 'attachmentable');
    }

    /**
     * Scope to filter active delivery companies.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter delivery companies based on various criteria.
     */
    public function scopeFilter($query, array $filters = [])
    {
        $query->withTranslation();

        if (!empty($filters['search'])) {
            $query->whereTranslationLike('name', '%' . $filters['search'] . '%');
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (!empty($filters['country'])) {
            $query->whereJsonContains('operating_countries', $filters['country']);
        }

        if (!empty($filters['zone_id'])) {
            $query->whereHas('shippingPrices', function ($q) use ($filters) {
                $q->where('zone_id', $filters['zone_id']);
            });
        }

        return $query;
    }

    /**
     * Check if the delivery company is currently open.
     */
    public function isOpen(): bool
    {
        $currentDay = now()->dayOfWeek;
        $currentTime = now()->format('H:i:s');

        $workingDay = $this->workingDays()
            ->where('day_of_week', $currentDay)
            ->first();

        if (!$workingDay) {
            return false;
        }

        return $currentTime >= $workingDay->open_time
            && $currentTime <= $workingDay->close_time;
    }

    /**
     * Get the delivery fee for a specific zone.
     */
    public function getDeliveryFeeForZone(int $zoneId): ?float
    {
        $shippingPrice = $this->shippingPrices()->where('zone_id', $zoneId)->first();
        return $shippingPrice?->base_price;
    }

    /**
     * Get the estimated delivery time for a specific zone.
     */
    public function getEstimatedTimeForZone(int $zoneId): ?int
    {
        $shippingPrice = $this->shippingPrices()->where('zone_id', $zoneId)->first();
        return $shippingPrice?->estimated_time ?? null;
    }

    /**
     * Find the best delivery company for a specific zone based on availability and fees.
     */
    public static function findBestForZone(int $zoneId): ?self
    {
        return self::active()
            ->whereHas('shippingPrices', function ($query) use ($zoneId) {
                $query->where('zone_id', $zoneId);
            })
            ->whereHas('workingDays', function ($query) {
                $query->where('day_of_week', now()->dayOfWeek)
                      ->where('open_time', '<=', now()->format('H:i:s'))
                      ->where('close_time', '>=', now()->format('H:i:s'));
            })
            ->orderBy(
                \App\Models\ShippingPrice::select('base_price')
                    ->whereColumn('zone_id', $zoneId)
                    ->limit(1)
            )
            ->first();
    }
}
