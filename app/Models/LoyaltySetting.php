<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoyaltySetting extends Model
{
    protected $fillable = [
        'is_enabled',
        'points_per_currency',
        'redemption_rate',
        'points_expiry_days',
        'minimum_redemption_points',
        'maximum_redemption_percentage',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'points_per_currency' => 'decimal:2',
        'redemption_rate' => 'decimal:2',
        'points_expiry_days' => 'integer',
        'minimum_redemption_points' => 'decimal:2',
        'maximum_redemption_percentage' => 'decimal:2',
    ];

    /**
     * Get the current active loyalty settings
     */
    public static function getSettings(): self
    {
        return self::first() ?? self::create([
            'is_enabled' => true,
            'points_per_currency' => 1.00,
            'redemption_rate' => 0.01,
            'points_expiry_days' => 365,
            'minimum_redemption_points' => 100,
            'maximum_redemption_percentage' => 50,
        ]);
    }

    /**
     * Check if loyalty program is enabled
     */
    public function isEnabled(): bool
    {
        return $this->is_enabled;
    }

    /**
     * Calculate points earned for given amount
     */
    public function calculatePoints(float $amount): float
    {
        return round($amount * $this->points_per_currency, 2);
    }

    /**
     * Calculate currency value for given points
     */
    public function calculateRedemptionValue(float $points): float
    {
        return round($points * $this->redemption_rate, 2);
    }

    /**
     * Get points expiry date
     */
    public function getExpiryDate(): string
    {
        return now()->addDays($this->points_expiry_days)->toDateString();
    }
}
