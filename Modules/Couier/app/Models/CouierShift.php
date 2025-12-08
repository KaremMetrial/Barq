<?php

namespace Modules\Couier\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CouierShift extends Model
{
    protected $fillable = [
        'start_time',
        'end_time',
        'expected_end_time',
        'is_open',
        'couier_id',
        'shift_template_id',
        'break_start',
        'break_end',
        'overtime_minutes',
        'overtime_pay',
        'total_orders',
        'total_earnings',
        'notes'
    ];

    protected $casts = [
        'is_open' => 'boolean',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'expected_end_time' => 'datetime',
        'break_start' => 'datetime',
        'break_end' => 'datetime',
        'overtime_minutes' => 'integer',
        'overtime_pay' => 'decimal:2',
        'total_orders' => 'integer',
        'total_earnings' => 'decimal:2'
    ];

    /**
     * Get the courier this shift belongs to
     */
    public function couier(): BelongsTo
    {
        return $this->belongsTo(Couier::class);
    }

    /**
     * Get the shift template used for this shift
     */
    public function shiftTemplate(): BelongsTo
    {
        return $this->belongsTo(ShiftTemplate::class);
    }

    /**
     * Calculate overtime minutes automatically
     */
    public function getOvertimeMinutesAttribute($value): int
    {
        // If already set, return it
        if ($value !== null) {
            return $value;
        }

        // Calculate if shift is closed
        if (!$this->end_time || !$this->expected_end_time) {
            return 0;
        }

        $diff = $this->end_time->diffInMinutes($this->expected_end_time, false);
        return $diff > 0 ? $diff : 0;
    }

    /**
     * Check if shift exceeded deadline
     */
    public function getIsLateAttribute(): bool
    {
        return $this->overtime_minutes > 0;
    }

    /**
     * Calculate total work hours (excluding break)
     */
    public function getTotalHoursAttribute(): float
    {
        if (!$this->start_time || !$this->end_time) {
            return 0;
        }

        $totalMinutes = $this->end_time->diffInMinutes($this->start_time);

        // Subtract break time if exists
        if ($this->break_start && $this->break_end) {
            $breakMinutes = $this->break_end->diffInMinutes($this->break_start);
            $totalMinutes -= $breakMinutes;
        }

        return round($totalMinutes / 60, 2);
    }

    /**
     * Scope for open shifts
     */
    public function scopeOpen($query)
    {
        return $query->where('is_open', true);
    }

    /**
     * Scope for closed shifts
     */
    public function scopeClosed($query)
    {
        return $query->where('is_open', false);
    }
}
