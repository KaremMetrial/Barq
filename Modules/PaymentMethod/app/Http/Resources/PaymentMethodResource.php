<?php

namespace Modules\PaymentMethod\Http\Resources;

use App\Enums\PaymentMethodStatus;
use App\Enums\SaleTypeEnum;
use App\Enums\PaymentMethodTypeEnum;
use Illuminate\Http\Request;
use App\Enums\PaymentMethodStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\PaymentMethodInputTypeEnum;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentMethodResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'is_cod' => $this->is_cod,
            'sort_order' => $this->sort_order,
        ];
    }
}
