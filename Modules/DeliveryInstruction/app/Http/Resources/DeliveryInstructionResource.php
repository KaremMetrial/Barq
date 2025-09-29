<?php

namespace Modules\DeliveryInstruction\Http\Resources;

use App\Enums\SaleTypeEnum;
use App\Enums\DeliveryInstructionTypeEnum;
use Illuminate\Http\Request;
use App\Enums\DeliveryInstructionStatusEnum;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryInstructionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id"=> $this->id,
            "title" => $this->title,
            "description" => $this->description,
            "is_active" => (bool) $this->is_active,
        ];
    }
}
