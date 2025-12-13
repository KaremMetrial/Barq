<?php

namespace Modules\Couier\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FullOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // Assignment Info
            'assignment_id' => $this->id,
            'status' => $this->status,
            'priority_level' => $this->priority_level,
            'expires_at' => $this->expires_at?->toISOString(),
            'accepted_at' => $this->accepted_at?->toISOString(),
            'started_at' => $this->started_at?->toISOString(),

            // Customer Information
            'customer' => [
                'name' => $this->order->user->name ?? 'Unknown Customer',
                'phone' => $this->order->user->phone ?? null,
                'email' => $this->order->user->email ?? null,
            ],

            // Delivery Address
            'delivery_address' => $this->order->deliveryAddress ? [
                'street' => $this->order->deliveryAddress->street,
                'building' => $this->order->deliveryAddress->building,
                'floor' => $this->order->deliveryAddress->floor,
                'apartment' => $this->order->deliveryAddress->apartment,
                'area' => $this->order->deliveryAddress->area?->name ?? null,
                'city' => $this->order->deliveryAddress->city?->name ?? null,
                'governorate' => $this->order->deliveryAddress->governorate?->name ?? null,
                'coordinates' => [
                    'lat' => $this->delivery_lat,
                    'lng' => $this->delivery_lng,
                ],
                'delivery_instructions' => $this->order->deliveryAddress->delivery_instructions,
            ] : null,

            // Store Information
            'store' => [
                'id' => $this->order->store->id,
                'name' => $this->order->store->name,
                'phone' => $this->order->store->phone,
                'address' => $this->order->store->address,
            ],

            // Order Details
            'order' => [
                'order_number' => $this->order->order_number,
                'order_type' => $this->order->type->value,
                'status' => $this->order->status->value,
                'created_at' => $this->order->created_at?->toISOString(),
                'notes' => $this->order->note,
            ],

            // Products - Detailed
            'products' => $this->order->orderItems->map(function ($item) {
                $product = $item->product;

                return [
                    'id' => $product->id,
                    'name_ar' => $product->translate('ar')?->name ?? $product->name,
                    'name_en' => $product->translate('en')?->name ?? $product->name,
                    'sku' => $product->sku,
                    'image' => $product->main_image_url,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->total_price / $item->quantity,
                    'total_price' => $item->total_price,
                    'notes' => $item->note,

                    // Product options/variants
                    'options' => $item->product_option_value_id ? json_decode($item->product_option_value_id, true) : [],

                    // Add-ons
                    'add_ons' => $item->addOns->map(function ($addOn) {
                        return [
                            'name_ar' => $addOn->translate('ar')?->name ?? $addOn->name,
                            'name_en' => $addOn->translate('en')?->name ?? $addOn->name,
                            'quantity' => $addOn->pivot->quantity,
                            'price' => $addOn->pivot->price_modifier,
                        ];
                    }),

                    // Allergens and special info
                    'allergens' => $product->productAllergens->pluck('name')->toArray(),
                    'special_instructions' => $product->special_instructions,
                    'preparation_time' => $product->preparation_time,
                ];
            }),

            // Payment Information
            'payment' => [
                'total_amount' => $this->order->total_amount,
                'delivery_fee' => $this->order->delivery_fee,
                'discount_amount' => $this->order->discount_amount,
                'tax_amount' => $this->order->tax_amount,
                'paid_amount' => $this->order->paid_amount,
                'payment_method' => $this->order->paymentMethod ? [
                    'id' => $this->order->paymentMethod->id,
                    'name' => $this->order->paymentMethod->name,
                    'type' => $this->order->paymentMethod->type,
                    'is_cod' => $this->order->paymentMethod->is_cod,
                ] : null,
                'payment_status' => $this->order->payment_status?->value,
                'cash_amount_due' => $this->calculateCashDue(),
            ],

            // Delivery Information
            'delivery' => [
                'estimated_delivery_time' => $this->order->estimated_delivery_time,
                'estimated_distance' => $this->estimated_distance_km,
                'estimated_duration' => $this->estimated_duration_minutes,
                'estimated_earning' => $this->estimated_earning,

                'actual_distance' => $this->actual_distance_km,
                'actual_duration' => $this->actual_duration_minutes,
                'actual_earning' => $this->actual_earning,

                'pickup_coordinates' => [
                    'lat' => $this->pickup_lat,
                    'lng' => $this->pickup_lng,
                ],

                'current_location' => [
                    'lat' => $this->current_courier_lat,
                    'lng' => $this->current_courier_lng,
                ],
            ],

            // Upload Status for Current Courier
            'upload_status' => [
                'can_upload_pickup_receipt' => $this->canUploadReceipt('pickup_receipt'),
                'can_upload_pickup_product' => $this->canUploadReceipt('pickup_product'),
                'can_upload_delivery_proof' => $this->canUploadReceipt('delivery_proof'),
                'can_upload_signature' => $this->canUploadReceipt('customer_signature'),
            ],

            // Existing uploads
            'receipts' => $this->receipts->map(function ($receipt) {
                return [
                    'id' => $receipt->id,
                    'type' => $receipt->type,
                    'file_name' => $receipt->file_name,
                    'url' => $receipt->url,
                    'file_size_human' => $receipt->file_size_human,
                    'uploaded_at' => $receipt->created_at->toISOString(),
                    'is_image' => $receipt->isImage(),
                ];
            }),

            // Shift Information
            'shift' => $this->courierShift ? [
                'id' => $this->courierShift->id,
                'start_time' => $this->courierShift->start_time?->toISOString(),
                'is_open' => $this->courierShift->is_open,
            ] : null,
        ];
    }

    /**
     * Check if courier can upload specific receipt type
     */
    protected function canUploadReceipt(string $type): bool
    {
        $allowedTypes = match($this->status) {
            'accepted' => ['pickup_product', 'pickup_receipt'],
            'in_transit' => ['pickup_product', 'pickup_receipt', 'delivery_proof', 'customer_signature'],
            'delivered', 'failed' => ['pickup_product', 'pickup_receipt', 'delivery_proof', 'customer_signature'],
            default => []
        };

        if (!in_array($type, $allowedTypes)) {
            return false;
        }

        // Check if already uploaded
        return !$this->receipts->where('type', $type)->count();
    }

    /**
     * Calculate cash amount due for COD orders
     */
    protected function calculateCashDue(): ?float
    {
        if ($this->order->paymentMethod?->is_cod && $this->order->payment_status !== 'paid') {
            return $this->order->total_amount - $this->order->paid_amount;
        }

        return null;
    }
}
