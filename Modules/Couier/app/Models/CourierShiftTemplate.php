<?php

namespace Modules\Couier\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourierShiftTemplate extends Model
{
    protected $fillable = [
        'courier_id',
        'shift_template_id',
        'is_active',
        'assigned_at',
        'assigned_by',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'assigned_at' => 'datetime',
    ];

    /**
     * Get the courier this assignment belongs to
     */
    public function courier(): BelongsTo
    {
        return $this->belongsTo(Couier::class);
    }

    /**
     * Get the shift template assigned
     */
    public function shiftTemplate(): BelongsTo
    {
        return $this->belongsTo(ShiftTemplate::class)->with('days');
    }

    /**
     * Get the admin who assigned this template
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(\Modules\Admin\Models\Admin::class, 'assigned_by');
    }

    /**
     * Get formatted weekly schedule for the courier
     */
    public function getWeeklyScheduleAttribute(): array
    {
        $schedule = [];

        // Map day numbers to Arabic names
        $dayNames = [
            0 => 'الأحد',
            1 => 'الاثنين',
            2 => 'الثلاثاء',
            3 => 'الأربعاء',
            4 => 'الخميس',
            5 => 'الجمعة',
            6 => 'السبت'
        ];

        foreach ($this->shiftTemplate->days as $day) {
            $schedule[] = [
                'day_of_week' => $day->day_of_week,
                'day_name' => $dayNames[$day->day_of_week] ?? '',
                'start_time' => $day->is_off_day ? null : $day->start_time,
                'end_time' => $day->is_off_day ? null : $day->end_time,
                'break_duration' => $day->break_duration,
                'is_off_day' => $day->is_off_day,
                'total_hours' => $day->total_hours,
            ];
        }

        return $schedule;
    }

    /**
     * Scope for active assignments only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific courier
     */
    public function scopeForCourier($query, $courierId)
    {
        return $query->where('courier_id', $courierId);
    }
}
