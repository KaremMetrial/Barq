<?php

namespace Modules\Order\Http\Resources;

use App\Enums\OrderStatus;
use App\Enums\SaleTypeEnum;
use App\Enums\OrderTypeEnum;
use Illuminate\Http\Request;
use App\Enums\OrderStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\OrderInputTypeEnum;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            "order_number"=> $this->order_number,
            "reference_code"=> $this->reference_code,
            "type"=> $this->type->value,
            "type_label"=> OrderTypeEnum::label($this->type->value),
            "status"=> $this->status?->value,
            "status_label"=> OrderStatus::label($this->status?->value),
            "note"=> $this->note,
            "is_read"=> (bool) $this->is_read,
            "total_amount"=> $this->total_amount,
            "discount_amount"=> $this->discount_amount,
            "paid_amount"=> $this->paid_amount,
            "delivery_fee"=> $this->delivery_fee,
            "tax_amount"=> $this->tax_amount,
            "service_fee"=> $this->service_fee,
            "payment_status"=> $this->payment_status?->value,
            "payment_status_label"=> PaymentStatusEnum::label($this->payment_status?->value),
            "otp_code"=> $this->otp_code,
            "requires_otp"=> (bool) $this->requires_otp,
            "delivery_address"=> $this->delivery_address,
            "tip_amount"=> $this->tip_amount,
            "estimated_delivery_time"=> $this->estimated_delivery_time?->format('Y-m-d H:i:s'),
            "delivered_at"=> $this->delivered_at,
            "actioned_by"=> $this->actioned_by,
            "store"=> $this->whenLoaded('store', function () {
                return [
                    'id' => $this->store->id,
                    'name' => $this->store->name,
                ];
            }),
            "user"=> $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                ];
            }),
            "courier"=> $this->whenLoaded('courier', function () {
                return [
                    'id' => $this->courier->id,
                    'name' => $this->courier->name,
                ];
            }),
            "pos_shift"=> $this->whenLoaded('posShift', function () {
                return [
                    'id' => $this->posShift->id,
                    'name' => $this->posShift->name,
                ];
            }),
        ];
    }
}
