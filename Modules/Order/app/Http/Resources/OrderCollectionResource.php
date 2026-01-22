<?php
namespace Modules\Order\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderCollectionResource extends JsonResource
{
    /**
     * Lighter version for list views
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'reference_code' => $this->reference_code,
            'type' => [
                'value' => $this->type?->value,
                'label' => $this->type?->value ? \App\Enums\OrderTypeEnum::label($this->type->value) : null,
            ],
            'status' => [
                'value' => $this->status?->value,
                'label' => $this->status?->value ? \App\Enums\OrderStatus::label($this->status->value) : null,
            ],
            'payment_status' => [
                'value' => $this->payment_status?->value,
                'label' => $this->payment_status?->value ? \App\Enums\PaymentStatusEnum::label($this->payment_status->value) : null,
            ],
            'total_amount' => (int) $this->total_amount,
            'final_amount' => (int) ($this->total_amount - $this->discount_amount + $this->delivery_fee + $this->service_fee + $this->tax_amount),
            'items_count' => $this->orderItems->count() ?? 0,

            'store' => [
                'id' => $this->store->id,
                'name' => $this->store->translations->first()?->name ?? 'N/A',
            ],

            'user' => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->first_name . ' ' . $this->user->last_name,
            ] : null,

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}

