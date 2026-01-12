<?php

namespace Modules\Store\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommissionStoreResource extends JsonResource
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
            'store_id' => $this->id,
            'name' => $this->name,
            'total_orders' => $this->total_orders ?? $this->orders()->count(),
            'total_earned_commission' => $this->total_earned_commission ?? $this->getTotalEarnedCommission(),
            'total_pending_commission' => $this->total_pending_commission ?? $this->getTotalPendingCommission(),
            'commission_amount' => $this->commission_amount,
            'commission_type' => $this->commission_type->value ?? $this->commission_type,
            'commission_type_label' => $this->commission_type ? \App\Enums\PlanTypeEnum::label($this->commission_type->value) : null,
            'status' => $this->status->value,
            'status_label' => \App\Enums\StoreStatusEnum::label($this->status->value),
            'logo' => $this->logo ? asset("storage/" . $this->logo) : null,
            'section' => $this->section ? $this->section->name : null,
            'owner' => $this->owner ? $this->owner->name : null,
            'is_active' => (bool) $this->is_active
        ];
    }
}
