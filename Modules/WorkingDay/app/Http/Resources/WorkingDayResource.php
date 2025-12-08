<?php

namespace Modules\WorkingDay\Http\Resources;

use App\Enums\WorkingDayEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkingDayResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "day_of_week" => $this->day_of_week->value,
            "day_of_week_label" => WorkingDayEnum::label($this->day_of_week->value),
            "open_time" => $this->open_time ?? null,
            "close_time" => $this->close_time ?? null,
            "store_id" => $this->store?->id,
        ];
    }
}
