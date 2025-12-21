<?php

namespace Modules\Couier\Http\Resources;

use Illuminate\Http\Request;
use App\Enums\UserStatusEnum;
use Modules\Address\Models\Address;
use App\Enums\CouierAvaliableStatusEnum;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Address\Http\Resources\AddressResource;
use Modules\Vehicle\Http\Resources\VehicleResource;

class CourierResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // Basic Information
            "id" => $this->id,
            "first_name" => $this->first_name,
            "last_name" => $this->last_name,
            "name" => $this->first_name . " " . $this->last_name,
            "email" => $this->email,
            "phone" => $this->phone,
            "avatar" => $this->avatar ? asset('storage/' . $this->avatar) : null,

            // Status & Availability
            "avaliable_status" => $this->avaliable_status?->value,
            "avaliable_status_label" => CouierAvaliableStatusEnum::label($this->avaliable_status?->value),
            "status" => $this->status?->value,
            "status_label" => UserStatusEnum::label($this->status?->value),

            // Professional Information
            "license_number" => $this->license_number,
            "driving_license" => $this->driving_license ? asset('storage/' . $this->driving_license) : null,
            "birthday" => $this->birthday,
            "avg_rate" => $this->avg_rate,

            // Store Information
            "store" => $this->whenLoaded('store', function () {
                return [
                    'id' => $this->store->id,
                    'name' => $this->store->name,
                    'logo' => $this->store->logo ? asset('storage/' . $this->store->logo) : null,
                    'phone' => $this->store->phone,
                    'address' => $this->store->address ? $this->store->address->getFullAddressAttribute() : null,
                ];
            }),

            // Performance Metrics
            "total_order" => $this->orders()->count(),
            "completed_orders" => $this->orders()->where('status', 'delivered')->count(),
            "total_earning" => $this->calculateCommission($this->orders()->where('status', 'delivered')),

            // Vehicle Information
            "vehicle" => $this->whenLoaded('vehicle', function() {
                $cv = $this->vehicle;
                if (!$cv) return null;

                return [
                    'id' => $cv->id,
                    'car_license' => $cv->car_license ? asset('storage/' . $cv->car_license) : null,
                    'car_model' => $cv->model,
                    'plate_number'  => $cv->plate_number,
                    'color' => $cv->color,
                    'vehicle' => $cv->vehicle ? new VehicleResource($cv->vehicle) : null,
                ];
            }),

            // Current Active Shift
            "current_shift" => $this->whenLoaded('shifts', function () {
                $currentShift = $this->shifts()
                    ->whereNull('end_time')
                    ->with('shiftTemplate')
                    ->first();

                return $currentShift ? [
                    'id' => $currentShift->id,
                    'started_at' => $currentShift->started_at,
                    'shift_template' => [
                        'name' => $currentShift->shiftTemplate?->name,
                        'start_time' => $currentShift->shiftTemplate?->start_time,
                        'end_time' => $currentShift->shiftTemplate?->end_time,
                    ]
                ] : null;
            }),

            // Zones Coverage
            "zones" => $this->whenLoaded('zonesToCover', function () {
                return $this->zonesToCover->map(function ($zone) {
                    return [
                        'id' => $zone->id,
                        'name' => $zone->name,
                        'city' => $zone->city?->name,
                    ];
                });
            }),

            // Statistics for Dashboard
            "stats" => (function () {
                // Wallet (Balance is a polymorphic relation stored in balances table)
                $balance = \Modules\Balance\Models\Balance::where('balanceable_id', $this->id)
                    ->where('balanceable_type', get_class($this))
                    ->first();

                $available = $balance ? (float) $balance->available_balance : 0.0;
                $totalBalance = $balance ? (float) $balance->total_balance : 0.0;
                $pending = $balance ? (float) $balance->pending_balance : 0.0;

                $deliveredCount = $this->orders()->where('status', 'delivered')->count();
                $onTheWayCount = $this->orders()->where('status', 'on_the_way')->count();

                return [
                    'today_orders' => $this->orders()->whereDate('created_at', today())->count(),
                    'week_orders' => $this->orders()->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                    'month_earnings' => $this->calculateCommission(
                        $this->orders()->where('status', 'delivered')->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)
                    ),
                    'total_earnings' => $this->calculateCommission($this->orders()->where('status', 'delivered')),

                    // Wallet info
                    'wallet_balance' => $available,
                    'wallet' => [
                        'total_balance' => $totalBalance,
                        'available_balance' => $available,
                        'pending_balance' => $pending,
                    ],

                    // Order counts
                    'cancelled_orders' => $this->orders()->where('status', 'cancelled')->count(),
                    'completed_orders' => $deliveredCount,
                    'total_deliveries' => $deliveredCount + $onTheWayCount,
                ];
            })(),

            // Timestamps
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
            'address' => new AddressResource($this->whenLoaded('address')),
            'attachments' => $this->whenLoaded('attachments', function () {
                return $this->attachments->map(function ($attachment) {
                    return [
                        'id' => $attachment->id,
                        'url' => $attachment->path ? asset('storage/' . $attachment->path) : null,
                        'name' => $attachment->name,
                    ];
                });
            }),
            'commission_type' => $this->commission_type->value,
            'commission_amount' => $this->commission_amount,
            'national_identity' => $this->whenLoaded('nationalIdentity', function () {
                $identity = $this->nationalIdentity;
                if (!$identity) return null;

                return [
                    'id' => $identity->id,
                    'national_id' => $identity->national_id,
                    'front_image' => $identity->front_image ? asset('storage/' . $identity->front_image) : null,
                    'back_image' => $identity->back_image ? asset('storage/' . $identity->back_image) : null,
                ];
            }),
        ];
    }

    /**
     * Calculate total commission for given orders
     *
     * @param \Illuminate\Database\Eloquent\Builder $ordersQuery
     * @return float
     */
    private function calculateCommission($ordersQuery): float
    {
        $commission = 0;

        $orders = $ordersQuery->get();

        foreach ($orders as $order) {
            if ($this->commission_type === 'percentage') {
                $commission += $order->delivery_fee * ($this->commission_amount / 100);
            } elseif ($this->commission_type === 'fixed') {
                $commission += $this->commission_amount;
            }
        }

        return round($commission, 2);
    }
}
