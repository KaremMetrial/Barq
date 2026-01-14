<?php

namespace Modules\Couier\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Modules\Couier\Observers\CourierOrderAssignmentObserver;
#[ObservedBy(CourierOrderAssignmentObserver::class)]
class CourierOrderAssignment extends Model
{
    protected $fillable = [
        'courier_id',
        'order_id',
        'courier_shift_id',
        'status',
        'assigned_at',
        'accepted_at',
        'expires_at',
        'started_at',
        'completed_at',
        'pickup_lat',
        'pickup_lng',
        'delivery_lat',
        'delivery_lng',
        'current_courier_lat',
        'current_courier_lng',
        'estimated_distance_km',
        'actual_distance_km',
        'estimated_duration_minutes',
        'actual_duration_minutes',
        'estimated_earning',
        'actual_earning',
        'priority_level',
        'courier_rating',
        'courier_feedback',
        'customer_rating',
        'customer_feedback',
        'rejection_reason',
        'notes',
        'assignment_metadata',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'accepted_at' => 'datetime',
        'expires_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'estimated_distance_km' => 'decimal:2',
        'actual_distance_km' => 'decimal:2',
        'estimated_earning' => 'decimal:2',
        'actual_earning' => 'decimal:2',
        'courier_rating' => 'integer',
        'customer_rating' => 'integer',
        'assignment_metadata' => 'array',
    ];

    /**
     * Get the courier this assignment belongs to
     */
    public function courier(): BelongsTo
    {
        return $this->belongsTo(Couier::class);
    }

    /**
     * Get the order assigned
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(\Modules\Order\Models\Order::class);
    }

    /**
     * Get the courier shift this assignment belongs to
     */
    public function courierShift(): BelongsTo
    {
        return $this->belongsTo(CouierShift::class);
    }

    /**
     * Get receipts for this assignment
     */
    public function receipts(): HasMany
    {
        return $this->hasMany(OrderReceipt::class, 'assignment_id');
    }

    /**
     * Get pickup coordinates as array
     */
    public function getPickupCoordinatesAttribute(): ?array
    {
        if ($this->pickup_lat && $this->pickup_lng) {
            return [
                'lat' => (float) $this->pickup_lat,
                'lng' => (float) $this->pickup_lng,
            ];
        }
        return null;
    }

    /**
     * Get delivery coordinates as array
     */
    public function getDeliveryCoordinatesAttribute(): ?array
    {
        if ($this->delivery_lat && $this->delivery_lng) {
            return [
                'lat' => (float) $this->delivery_lat,
                'lng' => (float) $this->delivery_lng,
            ];
        }
        return null;
    }

    /**
     * Get current courier coordinates as array
     */
    public function getCurrentCoordinatesAttribute(): ?array
    {
        if ($this->current_courier_lat && $this->current_courier_lng) {
            return [
                'lat' => (float) $this->current_courier_lat,
                'lng' => (float) $this->current_courier_lng,
            ];
        }
        return null;
    }

    /**
     * Check if assignment is expired
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Get remaining time in seconds
     */
    public function getTimeRemainingAttribute(): ?int
    {
        if ($this->expires_at) {
            return max(0, now()->diffInSeconds($this->expires_at, false));
        }
        return null;
    }

    /**
     * Calculate total duration if completed
     */
    public function getTotalDurationAttribute(): ?int
    {
        if ($this->started_at && $this->completed_at) {
            return $this->started_at->diffInMinutes($this->completed_at);
        }
        return null;
    }

    /**
     * Check if assignment can be accepted
     */
    public function canBeAccepted(): bool
    {
        return $this->status === 'assigned' && !$this->is_expired;
    }

    /**
     * Check if assignment can be started
     */
    public function canBeStarted(): bool
    {
        return $this->status === 'accepted';
    }

    /**
     * Accept the assignment
     */
    public function accept(): bool
    {
        if (!$this->canBeAccepted()) {
            return false;
        }

        $this->update([
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);

        return true;
    }

    /**
     * Start delivery
     */
    public function startDelivery(): bool
    {
        if (!$this->canBeStarted()) {
            return false;
        }

        $this->update([
            'status' => 'in_transit',
            'started_at' => now(),
        ]);

        return true;
    }

    /**
     * Mark as delivered
     */
    public function markDelivered(): bool
    {
        $this->update([
            'status' => 'delivered',
            'completed_at' => now(),
        ]);

        return true;
    }

    /**
     * Mark as failed
     */
    public function markFailed(string $reason = null): bool
    {
        $this->update([
            'status' => 'failed',
            'completed_at' => now(),
            'notes' => $reason ? ($this->notes . ' | Failed: ' . $reason) : $this->notes,
        ]);

        return true;
    }

    /**
     * Reject assignment
     */
    public function reject(string $reason = null): bool
    {
        $this->update([
            'status' => 'rejected',
            'completed_at' => now(),
            'rejection_reason' => $reason,
        ]);

        return true;
    }

    /**
     * Scope for active assignments
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['assigned', 'accepted', 'in_transit']);
    }

    /**
     * Scope for completed assignments
     */
    public function scopeCompleted($query)
    {
        return $query->whereIn('status', ['delivered', 'failed', 'rejected']);
    }

    /**
     * Scope for expired assignments
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    /**
     * Scope for specific courier
     */
    public function scopeForCourier($query, $courierId)
    {
        return $query->where('courier_id', $courierId);
    }
}
