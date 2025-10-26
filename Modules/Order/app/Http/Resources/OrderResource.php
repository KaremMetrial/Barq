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
            'id' => $this->id,
            'order_number' => $this->order_number,
            'reference_code' => $this->reference_code,
            'type' => [
                'value' => $this->type?->value,
                'label' => $this->type?->value ? OrderTypeEnum::label($this->type->value) : null,
            ],
            'status' => [
                'value' => $this->status?->value,
                'label' => $this->status?->value ? OrderStatus::label($this->status->value) : null,
            ],
            'payment_status' => [
                'value' => $this->payment_status?->value,
                'label' => $this->payment_status?->value ? PaymentStatusEnum::label($this->payment_status->value) : null,
            ],
            'note' => $this->note,
            'is_read' => $this->is_read,

            // Financial details
            'pricing' => [
                'total_amount' => (float) $this->total_amount,
                'discount_amount' => (float) $this->discount_amount,
                'delivery_fee' => (float) $this->delivery_fee,
                'service_fee' => (float) $this->service_fee,
                'tax_amount' => (float) $this->tax_amount,
                'tip_amount' => $this->tip_amount ? (float) $this->tip_amount : null,
                'paid_amount' => (float) $this->paid_amount,
                'final_amount' => (float) ($this->total_amount - $this->discount_amount + $this->delivery_fee + $this->service_fee + $this->tax_amount),
            ],

            // OTP details
            'otp' => [
                'requires_otp' => $this->requires_otp,
                'otp_code' => $this->when($this->shouldShowOtp($request), $this->otp_code),
            ],

            // Delivery details
            'delivery' => [
                'address' => $this->delivery_address,
                'estimated_delivery_time' => $this->estimated_delivery_time?->format('Y-m-d H:i:s'),
                'delivered_at' => $this->delivered_at?->format('Y-m-d H:i:s'),
            ],

            // Relations
            'store' => $this->when($this->relationLoaded('store'), function () {
                return [
                    'id' => $this->store->id,
                    'name' => $this->store->name ?? $this->store->translations->first()?->name,
                    'phone' => $this->store->phone,
                    'logo' => $this->store->logo,
                ];
            }),

            'user' => $this->when($this->relationLoaded('user'), function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->first_name . ' ' . $this->user->last_name,
                    'phone' => $this->user->phone,
                    'email' => $this->user->email,
                ];
            }),

            'courier' => $this->when($this->relationLoaded('courier'), function () {
                return $this->courier ? [
                    'id' => $this->courier->id,
                    'name' => $this->courier->first_name . ' ' . $this->courier->last_name,
                    'phone' => $this->courier->phone,
                    'avatar' => $this->courier->avatar,
                ] : null;
            }),

            'items' => OrderItemResource::collection($this->whenLoaded('items')->load('product', 'productOptionValue', 'addOns')),

            'coupon' => $this->when($this->relationLoaded('coupon'), function () {
                return $this->coupon ? [
                    'id' => $this->coupon->id,
                    'code' => $this->coupon->code,
                    'discount_type' => $this->coupon->discount_type,
                    'discount_amount' => (float) $this->coupon->discount_amount,
                ] : null;
            }),

            'status_history' => OrderStatusHistoryResource::collection($this->whenLoaded('statusHistories')),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
    private function shouldShowOtp(Request $request): bool
    {
        // Show OTP only to authorized users (courier, store owner, admin)
        $user = $request->user();

        if (!$user) {
            return false;
        }

        // Check different auth guards
        if (auth('admin')->check()) {
            return true; // Admin can see all OTPs
        }

        if (auth('vendor')->check()) {
            return $this->store_id === auth('vendor')->user()->store_id; // Vendor can see OTPs for their store orders
        }

        if (auth('user')->check()) {
            return $user->id === $this->user_id; // User can see their own order OTPs
        }

        // Courier check (assuming courier uses sanctum guard)
        if ($user instanceof \Modules\Couier\Models\Couier) {
            return true; // Courier assigned to order can see OTP
        }

        return false;
    }
}
