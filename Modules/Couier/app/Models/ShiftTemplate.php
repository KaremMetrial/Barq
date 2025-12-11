<?php

namespace Modules\Couier\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShiftTemplate extends Model
{
    protected $fillable = [
        'name',
        'is_active',
        'is_flexible',
        'store_id'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_flexible' => 'boolean',
        'store_id' => 'integer'
    ];

    /**
     * Get the store that owns this template
     */
    public function store()
    {
        return $this->belongsTo(\Modules\Store\Models\Store::class);
    }

    /**
     * Get the days for this shift template
     */
    public function days(): HasMany
    {
        return $this->hasMany(ShiftTemplateDay::class);
    }

    /**
     * Get courier shifts using this template
     */
    public function courierShifts(): HasMany
    {
        return $this->hasMany(CouierShift::class);
    }

    /**
     * Get couriers assigned to this template
     */
    public function courierAssignments(): HasMany
    {
        return $this->hasMany(CourierShiftTemplate::class);
    }

    /**
     * Get active courier assignments for this template
     */
    public function activeCourierAssignments()
    {
        return $this->courierAssignments()->active()->with('courier');
    }

    /**
     * Get the count of assigned couriers
     */
    public function getAssignedCouriersCountAttribute(): int
    {
        return $this->activeCourierAssignments()->count();
    }

    /**
     * Get active templates only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the day configuration for a specific day of week
     */
    public function getDayConfig(int $dayOfWeek): ?ShiftTemplateDay
    {
        return $this->days()->where('day_of_week', $dayOfWeek)->first();
    }
}
