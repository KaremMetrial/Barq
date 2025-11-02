<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Enums\LoyaltyTrransactionTypeEnum;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'points',
        'points_balance_after',
        'description',
        'referenceable_type',
        'referenceable_id',
        'expires_at',
    ];

    protected $casts = [
        'points' => 'decimal:2',
        'points_balance_after' => 'decimal:2',
        'expires_at' => 'datetime',
        'type' => LoyaltyTrransactionTypeEnum::class,
    ];

    /**
     * Get the user that owns the transaction
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\Modules\User\Models\User::class);
    }

    /**
     * Get the referenceable model (Order, Admin, etc.)
     */
    public function referenceable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope for earned transactions
     */
    public function scopeEarned($query)
    {
        return $query->where('type', 'earned');
    }

    /**
     * Scope for redeemed transactions
     */
    public function scopeRedeemed($query)
    {
        return $query->where('type', 'redeemed');
    }

    /**
     * Scope for expired transactions
     */
    public function scopeExpired($query)
    {
        return $query->where('type', 'expired');
    }

    /**
     * Scope for active (non-expired) transactions
     */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Check if transaction is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
