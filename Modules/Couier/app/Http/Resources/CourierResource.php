<?php

namespace Modules\Couier\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Enums\UserStatusEnum;
use Modules\Address\Models\Address;
use App\Enums\CouierAvaliableStatusEnum;
use Stevebauman\Location\Facades\Location;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Address\Http\Resources\AddressResource;
use Modules\Vehicle\Http\Resources\VehicleResource;
use Modules\Couier\Services\CourierShiftService;

class CourierResource extends JsonResource
{
    protected $courierShiftService;

    public function __construct($resource, ?CourierShiftService $courierShiftService = null)
    {
        parent::__construct($resource);
        $this->courierShiftService = $courierShiftService ?? app(CourierShiftService::class);
    }

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
            "phone_code" => $this->phone_code,
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
                        'currency_factor' => $zone->getCurrencyFactor(),
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
                        'created_at' => $attachment?->created_at?->format('Y-m-d H:i:s'),
                    ];
                });
            }),
            'commission_type' => $this->commission_type->value,
            'commission_amount' => $this->commission_amount,
            'currency_factor' => $this->store->getCurrencyFactor(),
            'currency_code' => $this->store->getCurrencyCode(),
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
            'iban' => $this->iban,

            'auto_accept_orders' => (bool) false, // $this->auto_accept_orders,
            'accept_overtime' => (bool) false, // $this->accept_overtime,

            'shift' => $this->getNextShiftData(),
        ];
    }

    /**
     * Generate structured data for next shift display
     *
     * @return array
     */
    private function getNextShiftData(): array
    {
        // Get next shift data using the courier shift service
        $nextShift = $this->courierShiftService->getNextShift($this->id);

        // Initialize default values
        $day = null;
        $duration = null;

        // Format shift data if next shift exists
        if ($nextShift && isset($nextShift['scheduled_date'])) {
            // Format day in Arabic: "الخميس، 06 أغسطس 2025"
            $shiftDate = Carbon::parse($nextShift['scheduled_date']);
            $day = $shiftDate->locale('ar')->isoFormat('dddd, D MMMM YYYY');

            // Format duration: "(8h) 08:00 - 16:00"
            if (isset($nextShift['scheduled_start_time']) && isset($nextShift['scheduled_end_time'])) {
                $startTime = Carbon::parse($nextShift['scheduled_start_time'])->format('H:i');
                $endTime = Carbon::parse($nextShift['scheduled_end_time'])->format('H:i');

                // Calculate duration in minutes first, then convert to hours
                $startCarbon = Carbon::parse($nextShift['scheduled_start_time']);
                $endCarbon = Carbon::parse($nextShift['scheduled_end_time']);
                $durationMinutes = abs($endCarbon->diffInMinutes($startCarbon)); // Use abs() to prevent negative

                // Only show duration if it's reasonable (at least 1 minute)
                if ($durationMinutes >= 1) {
                    $durationHours = round($durationMinutes / 60); // Round to whole hours
                    $duration = "({$durationHours}h) {$startTime} - {$endTime}";
                } else {
                    $duration = null; // Don't show duration for invalid shifts
                }
            }
        }

        // Handle location data
        $lat = request()->header('lat');
        $long = request()->header('long');
        $location = null;

        if ($lat && $long) {
            try {
                $zone = app(\Modules\Address\Services\AddressService::class)->getAddressByLatLong($lat, $long);
                $location = $zone?->getFullAddressAttribute();
            } catch (\Exception $e) {
                // Fallback to IP-based location if zone lookup fails
            }
        }

        if (!$location) {
            try {
                $position = Location::get(request()->ip());
                $location = $position ? $position->cityName . ', ' . $position->countryName . ', ' . $position->regionName : null;
            } catch (\Exception $e) {
                $location = null;
            }
        }

        return [
            'day' => $day,
            'duration' => $duration,
            'location' => $location,
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
        $orders = $ordersQuery->get();
        $totalEarnings = 0;

        foreach ($orders as $order) {
            $deliveryFee = $order->delivery_fee ?? 0;
            $commission = $this->resource->calculateCommission($deliveryFee);
            $totalEarnings += ($deliveryFee - $commission);
        }

        return round($totalEarnings, 2);
    }
}
