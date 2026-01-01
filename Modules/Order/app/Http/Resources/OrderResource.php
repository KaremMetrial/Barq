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
use Modules\Review\Http\Resources\ReviewResource;
use Modules\Couier\Services\CourierLocationCacheService;

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
                'value' => $this->getUserVisibleStatus($request),
                'label' => OrderStatus::label($this->getUserVisibleStatus($request)),
                'actual_value' => $this->when($this->isAdminOrVendor($request), $this->status?->value),
            ],
            'payment_status' => [
                'value' => $this->payment_status?->value,
                'label' => $this->payment_status?->value ? PaymentStatusEnum::label($this->payment_status->value) : null,
            ],
            'note' => $this->note,
            'is_read' => $this->is_read,

            // Financial details
            'pricing' => [
                'total_amount' => (int) $this->total_amount,
                'discount_amount' => (int) $this->discount_amount,
                'delivery_fee' => (int) $this->delivery_fee,
                'service_fee' => (int) $this->service_fee,
                'tax_amount' => (int) $this->tax_amount,
                'tip_amount' => (int) ($this->tip_amount ?? 0),
                'paid_amount' => (int) $this->paid_amount,
                'final_amount' => (int) ($this->total_amount - $this->discount_amount + $this->delivery_fee + $this->service_fee + $this->tax_amount),
                'symbol_currency' => $this->store?->currency_code ?? $this->store?->address?->zone?->city?->governorate?->country?->currency_symbol ?? 'EGP',
                'currency_factor' => $this->store?->currency_factor ?? $this->store?->address?->zone?->city?->governorate?->country?->currency_factor ?? 100,
            ],

            // OTP details
            'otp' => [
                'requires_otp' => $this->requires_otp,
                'otp_code' => $this->when($this->shouldShowOtp($request), $this->otp_code),
            ],

            // Delivery details
            'delivery' => [
                'address' => $this->when($this->relationLoaded('deliveryAddress'), function () {
                    return $this->deliveryAddress ? [
                        'id' => $this->deliveryAddress->id,
                        'name' => $this->deliveryAddress->name,
                        'phone' => $this->deliveryAddress->phone,
                        'type' => $this->deliveryAddress->type?->value,
                        'address_line_1' => $this->deliveryAddress->address_line_1,
                        'address_line_2' => $this->deliveryAddress->address_line_2,
                        'latitude' => (string) $this->deliveryAddress->latitude,
                        'longitude' => (string) $this->deliveryAddress->longitude,
                        'full_address' => $this->deliveryAddress->getFullAddressAttribute(),
                    ] : null;
                }),
                'estimated_delivery_time' => $this->getEstimatedDeliveryTime($request),

                'delivered_at' => $this->delivered_at?->format('Y-m-d H:i:s'),
            ],

            // Relations
            'store' => $this->when($this->relationLoaded('store'), function () {
                return [
                    'id' => $this->store->id,
                    'name' => $this->store->name ?? $this->store->translations->first()?->name,
                    'phone' => $this->store->phone,
                    'logo' => $this->store->logo ? asset('storage/' . $this->store->logo) : null,
                ];
            }),

            'user' => $this->when($this->relationLoaded('user'), function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->first_name . ' ' . $this->user->last_name,
                    'phone' => $this->user->phone,
                    'email' => $this->user->email,
                    'address' => $this->deliveryAddress ? $this->deliveryAddress->getFullAddressAttribute() : null,
                ];
            }),

            'courier' => $this->when($this->relationLoaded('courier'), function () {
                $locationCache = app(CourierLocationCacheService::class);
                $courierLocation = $this->courier ? $locationCache->getCourierLocation($this->courier->id) : null;

                return $this->courier ? [
                    'id' => $this->courier->id,
                    'name' => $this->courier->first_name . ' ' . $this->courier->last_name,
                    'phone' => $this->courier->phone,
                    'avatar' => $this->courier->avatar ? asset('storage/' . $this->courier->avatar) : null,
                    'unread_messages_count' => (int) $this->courierUnreadMessagesCount(),
                    'lat' => $courierLocation ? (string) $courierLocation['lat'] : '',
                    'lng' => $courierLocation ? (string) $courierLocation['lng'] : '',
                ] : null;
            }),

            'items' => OrderItemResource::collection($this->whenLoaded('items')),

            'coupon' => $this->when($this->relationLoaded('coupon'), function () {
                return $this->coupon ? [
                    'id' => $this->coupon->id,
                    'code' => $this->coupon->code,
                    'discount_type' => $this->coupon->discount_type,
                    'discount_amount' => (float) $this->coupon->discount_amount,
                ] : null;
            }),

            'status_history' => OrderStatusHistoryResource::collection($this->whenLoaded('statusHistories')),

            'payment_method' => $this->when($this->relationLoaded('paymentMethod'), function () {
                return $this->paymentMethod ? [
                    'id' => $this->paymentMethod->id,
                    'name' => $this->paymentMethod->name,
                    'is_cod' => $this->paymentMethod->is_cod,
                ] : null;
            }),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),

            'review' => $this->when($request->routeIs('*.show'), function () {
                $review = $this->reviews()->where('reviewable_id', $this->user_id)->where('reviewable_type', 'user')->first();
                return $review ? new ReviewResource($review) : null;
            }),
            'remaining_time_to_cancel_order' => $this->getRemainingTimeToCancelOrder(),
        ];
    }
    private function getRemainingTimeToCancelOrder(): ?int
    {
        $cancellationWindowMinutes = (int) config('settings.order.cancellation_window_minutes', 5);

        if (!$this->created_at) {
            return null;
        }

        $cancellationDeadline = $this->created_at->addMinutes($cancellationWindowMinutes);
        $now = now();

        if ($now->greaterThanOrEqualTo($cancellationDeadline)) {
            return 0;
        }

        return $now->diffInMinutes($cancellationDeadline);
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

    private function getEstimatedDeliveryTime(Request $request): ?string
    {
        if (!$this->estimated_delivery_time) {
            return null;
        }

        $data = json_decode($this->estimated_delivery_time, true);
        if (!$data) {
            return null;
        }

        // Get Accept-Language header, default to 'en'
        $acceptLanguage = $request->header('Accept-Language', 'en');
        $isArabic = str_contains(strtolower($acceptLanguage), 'ar');

        return $isArabic ? ($data['formatted_ar'] ?? null) : ($data['formatted_en'] ?? null);
    }

    /**
     * Get user-visible status (maps internal statuses to simplified versions)
     */
    private function getUserVisibleStatus(Request $request): ?string
    {
        if (!$this->status) {
            return null;
        }

        // Admins and vendors see actual status
        if ($this->isAdminOrVendor($request)) {
            return $this->status->value;
        }

        // Map internal statuses to user-visible ones
        $statusMapping = [
            'pending' => 'pending',
            'confirmed' => 'pending',              // Hide 'confirmed' - show as 'pending'
            'processing' => 'processing',
            'ready_for_delivery' => 'processing',  // Hide 'ready_for_delivery' - show as 'processing'
            'on_the_way' => 'on_the_way',
            'delivered' => 'delivered',
            'cancelled' => 'cancelled',
        ];

        return $statusMapping[$this->status->value] ?? $this->status->value;
    }

    /**
     * Check if the request is from an admin or vendor
     */
    private function isAdminOrVendor(Request $request): bool
    {
        return auth('admin')->check() || auth('vendor')->check();
    }
}
