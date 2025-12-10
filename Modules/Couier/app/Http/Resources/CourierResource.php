<?php

namespace Modules\Couier\Http\Resources;

use Illuminate\Http\Request;
use App\Enums\CouierTypeEnum;
use App\Enums\UserStatusEnum;
use App\Enums\CouierAvaliableStatusEnum;
use Illuminate\Http\Resources\Json\JsonResource;
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
                    'address' => $this->store->address?->getFullAddressAttribute(),
                ];
            }),

            // Performance Metrics
            "total_order" => $this->orders()->count(),
            "completed_orders" => $this->orders()->whereHas('status', function($query) {
                $query->where('status', 'delivered');
            })->count(),
            "total_earning" => $this->orders()->whereHas('status', function($query) {
                $query->where('status', 'delivered');
            })->sum('courier_commission'),

            // Vehicle Information
            "vehicle" => new VehicleResource($this->whenLoaded('vehicle')),

            // Current Active Shift
            "current_shift" => $this->whenLoaded('shifts', function () {
                $currentShift = $this->shifts()
                    ->whereNull('ended_at')
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
            "zones" => $this->whenLoaded('zones', function () {
                return $this->zones->map(function ($zone) {
                    return [
                        'id' => $zone->id,
                        'name' => $zone->name,
                        'city' => $zone->city?->name,
                    ];
                });
            }),

            // Statistics for Dashboard
            "stats" => [
                'today_orders' => $this->orders()
                    ->whereDate('created_at', today())
                    ->count(),
                'week_orders' => $this->orders()
                    ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
                    ->count(),
                'month_earnings' => $this->orders()
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->whereHas('status', function($query) {
                        $query->where('status', 'delivered');
                    })
                    ->sum('courier_commission'),
            ],

            // Timestamps
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
        ];
    }
}
