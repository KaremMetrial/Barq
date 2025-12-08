<?php

namespace Modules\Couier\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CourierShiftResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'courier_id' => $this->couier_id,
            'shift_template' => new ShiftTemplateResource($this->whenLoaded('shiftTemplate')),
            'start_time' => $this->start_time?->format('Y-m-d H:i:s'),
            'end_time' => $this->end_time?->format('Y-m-d H:i:s'),
            'expected_end_time' => $this->expected_end_time?->format('Y-m-d H:i:s'),
            'break_start' => $this->break_start?->format('Y-m-d H:i:s'),
            'break_end' => $this->break_end?->format('Y-m-d H:i:s'),
            'is_open' => (bool) $this->is_open,
            'overtime_minutes' => $this->overtime_minutes,
            'overtime_pay' => $this->overtime_pay,
            'is_late' => $this->is_late,
            'total_hours' => $this->total_hours,
            'total_orders' => $this->total_orders,
            'total_earnings' => $this->total_earnings,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
