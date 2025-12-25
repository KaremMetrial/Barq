<?php

namespace Modules\Couier\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ShiftTemplateDayResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'day_of_week' => $this->day_of_week,
            'day_name' => $this->day_name,
            'start_time' => $this->start_time?->format('H:i:s'),
            'end_time' => $this->end_time?->format('H:i:s'),
            'break_duration' => $this->break_duration,
            'is_off_day' => (bool) $this->is_off_day,
            'total_hours' => $this->total_hours,
        ];
    }
}
