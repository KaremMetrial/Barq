<?php

namespace Modules\Reward\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Coupon\Models\Coupon;
use Modules\Country\Models\Country;
use App\Enums\RewardType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reward extends Model
{
    protected $fillable = [
        'title',
        'description',
        'image',
        'type',
        'points_cost',
        'value_amount',
        'coupon_id',
        'country_id',
        'is_active',
        'start_date',
        'end_date',
        'usage_count',
        'max_redemptions_per_user',
        'total_redemptions',
        'is_it_for_loyalty_points',
        'is_it_for_spendings'
    ];

    protected $casts = [
        'type' => RewardType::class,
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
        'points_cost' => 'integer',
        'value_amount' => 'decimal:2',
        'usage_count' => 'integer',
        'max_redemptions_per_user' => 'integer',
        'total_redemptions' => 'integer',
    ];

    /**
     * Get the coupon associated with this reward
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * Get the country this reward is available in
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get all redemptions for this reward
     */
    public function redemptions(): HasMany
    {
        return $this->hasMany(RewardRedemption::class);
    }
    public function countOfUse()
    {
        return $this->redemptions()->count();
    }
    
    /**
     * Scope for active rewards
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('start_date')
                    ->orWhere('start_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            });
    }

    /**
     * Scope for rewards by country
     */
    public function scopeForCountry($query, $countryId)
    {
        return $query->where(function ($q) use ($countryId) {
            $q->where('country_id', $countryId)
                ->orWhereNull('country_id');
        });
    }

    /**
     * Scope for available rewards (not reached usage limit)
     */
    public function scopeAvailable($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('usage_count')
                ->orWhereRaw('total_redemptions < usage_count');
        });
    }

    /**
     * Check if reward is currently active
     */
    public function isActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();

        if ($this->start_date && $now->lt($this->start_date)) {
            return false;
        }

        if ($this->end_date && $now->gt($this->end_date)) {
            return false;
        }

        return true;
    }

    /**
     * Check if reward has reached usage limit
     */
    public function hasReachedLimit(): bool
    {
        if (!$this->usage_count) {
            return false;
        }

        return $this->total_redemptions >= $this->usage_count;
    }

    /**
     * Check if user can redeem this reward
     */
    public function canUserRedeem($userId): bool
    {
        if (!$this->max_redemptions_per_user) {
            return true;
        }

        $userRedemptions = $this->redemptions()
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->count();

        return $userRedemptions < $this->max_redemptions_per_user;
    }

    /**
     * Scope filter
     */
    public function scopeFilter($query, $filters)
    {
        if (isset($filters['search'])) {
            $query->where('title', 'like', '%' . $filters['search'] . '%');
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['country_id'])) {
            $query->forCountry($filters['country_id']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }
        if(isset($filters['except_prize'])){
            $query->where('type', '!=', 'prize');
        }
        return $query->latest();
    }
    public static function getRewardStats($filters = [])
    {
        // Base query for active rewards
        $query = self::query()->active()->filter($filters);

        // Aggregating and getting the statistics
        return $query->select(
            \DB::raw('COUNT(DISTINCT redemptions.user_id) as number_of_customers'), // Number of unique customers who redeemed
            \DB::raw('SUM(redemptions.points_spent) as total_points_spent'), // Total points spent on redemptions
            \DB::raw('SUM(value_amount) as total_value_redeemed'), // Total value redeemed
            \DB::raw('SUM(total_redemptions) as total_redemptions'), // Total redemptions made
            \DB::raw('MAX(redemptions.created_at) as last_redemption_date') // Last redemption date
        )
        ->leftJoin('reward_redemptions as redemptions', 'reward_redemptions.reward_id', '=', 'rewards.id')
        ->groupBy('rewards.id')
        ->first();
    }

}
