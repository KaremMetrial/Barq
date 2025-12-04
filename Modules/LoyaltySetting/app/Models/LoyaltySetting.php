<?php

namespace Modules\LoyaltySetting\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Country\Models\Country;

class LoyaltySetting extends Model
{
    protected $fillable = [
        'country_id',
        'earn_rate',
        'min_order_for_earn',
        'referral_points',
        'rating_points',
    ];

    protected $casts = [
        'country_id' => 'integer',
        'earn_rate' => 'decimal:2',
        'min_order_for_earn' => 'decimal:2',
        'referral_points' => 'integer',
        'rating_points' => 'integer',
    ];

    /**
     * Get the current active loyalty settings
     */
    public static function getSettings(): self
    {
        return self::first() ?? self::create([
            'country_id' => 1,
            'earn_rate' => 0.10,
            'min_order_for_earn' => 10.00,
            'referral_points' => 200,
            'rating_points' => 30,
        ]);
    }

    /**
     * Calculate points earned for given amount
     */
    public function calculatePoints(float $amount): float
    {
        return round($amount * $this->earn_rate, 2);
    }
    public function country()
    {
        return $this->belongsTo(Country::class);
    }
    public function scopeFilter($query, $filters)
    {
        if (isset($filters['search'])) {
            $query->whereTranslationLike('name', '%' . $filters['search'] . '%')->orWhere('code', '%' . $filters['search'] . '%');
        }
        return $query->with('country')->latest();
    }
}
