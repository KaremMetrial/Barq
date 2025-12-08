<?php

namespace Modules\Couier\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShiftTemplateDay extends Model
{
    protected $fillable = [
        'shift_template_id',
        'day_of_week',
        'start_time',
        'end_time',
        'break_duration',
        'is_off_day'
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'break_duration' => 'integer',
        'is_off_day' => 'boolean',
        'start_time' => 'datetime:H:i:s',
        'end_time' => 'datetime:H:i:s'
    ];

    /**
     * Get the shift template this day belongs to
     */
    public function shiftTemplate(): BelongsTo
    {
        return $this->belongsTo(ShiftTemplate::class);
    }

    /**
     * Get the day name in Arabic
     */
    public function getDayNameAttribute(): string
    {
        $days = [
            0 => 'الأحد',
            1 => 'الاثنين',
            2 => 'الثلاثاء',
            3 => 'الأربعاء',
            4 => 'الخميس',
            5 => 'الجمعة',
            6 => 'السبت'
        ];

        return $days[$this->day_of_week] ?? '';
    }

    /**
     * Calculate total work hours for this day
     */
    public function getTotalHoursAttribute(): float
    {
        if ($this->is_off_day || !$this->start_time || !$this->end_time) {
            return 0;
        }

        $start = \Carbon\Carbon::parse($this->start_time);
        $end = \Carbon\Carbon::parse($this->end_time);
        $totalMinutes = $end->diffInMinutes($start);

        // Subtract break duration
        $workMinutes = $totalMinutes - $this->break_duration;

        return round($workMinutes / 60, 2);
    }
}
