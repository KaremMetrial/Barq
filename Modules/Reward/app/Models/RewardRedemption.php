<?php

namespace Modules\Reward\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RewardRedemption extends Model
{
    protected $fillable = [
        'user_id',
        'reward_id',
        'points_spent',
        'reward_value_received',
        'coupon_code',
        'status',
        'redeemed_at',
        'expires_at',
    ];

    protected $casts = [
        'points_spent' => 'integer',
        'reward_value_received' => 'decimal:2',
        'redeemed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the user that redeemed the reward
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\Modules\User\Models\User::class);
    }

    /**
     * Get the reward that was redeemed
     */
    public function reward(): BelongsTo
    {
        return $this->belongsTo(Reward::class);
    }

    /**
     * Scope for completed redemptions
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for pending redemptions
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for failed redemptions
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Check if redemption is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
