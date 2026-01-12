<?php

namespace Modules\Couier\Services;

use Carbon\Carbon;
use App\Models\Attachment;
use App\Enums\WorkingDayEnum;
use App\Helpers\CurrencyHelper;
use App\Traits\FileUploadTrait;
use App\Models\NationalIdentity;
use Modules\Couier\Models\Couier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Stevebauman\Location\Facades\Location;
use Illuminate\Database\Eloquent\Collection;
use Modules\Zone\Http\Resources\ZoneResource;
use Modules\Store\Repositories\StoreRepository;
use Modules\Couier\Repositories\CouierRepository;
use Modules\Couier\Http\Resources\FullOrderResource;
use Modules\Couier\Http\Resources\CourierShiftResource;

class CouierService
{
    use FileUploadTrait;
    public function __construct(
        protected CouierRepository $CouierRepository,
        protected StoreRepository $storeRepository,
        protected CourierShiftService $courierShiftService
    ) {
    }

    public function getAllCouiers($filters = [])
    {
        return $this->CouierRepository->paginate($filters);
    }

    public function createCouier(array $data): ?Couier
    {
        // Extract courier data
        if (isset($data['courier']['avatar']) && request()->hasFile('courier.avatar')) {
            $data['courier']['avatar'] = $this->upload(
                request(),
                'courier.avatar',
                'uploads/avatars',
                'public'
            );
        }
        if (isset($data['courier']['driving_license']) && request()->hasFile('courier.driving_license')) {
            $data['courier']['driving_license'] = $this->upload(
                request(),
                'courier.driving_license',
                'uploads/driving_licenses',
                'public'
            );
        }

        // Create Couier
        $courierData = array_filter($data['courier'], fn ($value) => !blank($value));

        if (isset($courierData['commission_amount']) && isset($courierData['store_id'])) {
            $store = $this->storeRepository->find($courierData['store_id']);
            if ($store) {
                $factor = $store->getCurrencyFactor();
                $courierData['commission_amount'] = CurrencyHelper::priceToUnsignedBigInt($courierData['commission_amount'], $factor);
            } else {
                // fallback to default factor if store not found
                $courierData['commission_amount'] = CurrencyHelper::priceToUnsignedBigInt($courierData['commission_amount']);
            }
        }

        $couier = $this->CouierRepository->create($courierData)->refresh();
        // Create National Identity
        if (isset($data['nationalID'])) {
            $nationalIDData = $data['nationalID'];
            if (request()->hasFile('nationalID.front_image')) {
                $nationalIDData['front_image'] = $this->upload(
                    request(),
                    'nationalID.front_image',
                    'uploads/national_ids',
                    'public'
                );
            }
            if (request()->hasFile('nationalID.back_image')) {
                $nationalIDData['back_image'] = $this->upload(
                    request(),
                    'nationalID.back_image',
                    'uploads/national_ids',
                    'public'
                );
            }
            $nationalIDData['identityable_id'] = $couier->id;
            $nationalIDData['identityable_type'] = "courier";
            NationalIdentity::create($nationalIDData);
        }

        // Create Vehicle
        if (isset($data['vehicle'])) {
            if (isset($data['vehicle']['car_license']) && request()->hasFile('vehicle.car_license')) {
                $data['vehicle']['car_license'] = $this->upload(
                    request(),
                    'vehicle.car_license',
                    'uploads/car_licenses',
                    'public'
                );
            }
            $vehicleData = $data['vehicle'];
            $couier->vehicle()->create($vehicleData);
        }

        // Create Attachments
        if (isset($data['attachment'])) {
            foreach ($data['attachment'] as $index => $attachmentData) {
                if (request()->hasFile('attachment.' . $index . '.path')) {
                    $attachmentData['path'] = $this->upload(
                        request(),
                        'attachment.' . $index . '.path',
                        'uploads/attachments',
                        'public'
                    );
                }
                $attachmentData['_id'] = $couier->id;
                $attachmentData['_type'] = 'courier';
                Attachment::create($attachmentData);
            }
        }

        // Attach Zones
        if (isset($data['zones_to_cover'])) {
            $couier->zonesToCover()->sync($data['zones_to_cover']);
        }

        if (isset($data['address'])) {
            $addressData = $data['address'];
            $couier->address()->create($addressData);
        }
        return $couier->refresh();
    }

    public function getCouierById(int $id)
    {
        return $this->CouierRepository->find($id);
    }

    public function updateCouier(int $id, array $data)
    {
        $courierData = $data['courier'] ?? [];
        if (isset($data['courier']['avatar']) && request()->hasFile('courier.avatar')) {
            $courierData['avatar'] = $this->upload(
                request(),
                'courier.avatar',
                'uploads/avatars',
                'public'
            );
        }
        if (isset($data['courier']['driving_license']) && request()->hasFile('courier.driving_license')) {
            $courierData['driving_license'] = $this->upload(
                request(),
                'courier.driving_license',
                'uploads/driving_licenses',
                'public'
            );
        }
        $courierData = array_filter($courierData, fn ($value) => !blank($value));

        if (isset($courierData['commission_amount'])) {
            $factor = 100; // default
            $store = null;
            if (isset($courierData['store_id'])) {
                $store = $this->storeRepository->find($courierData['store_id']);
            } else {
                $couier = $this->CouierRepository->find($id);
                if ($couier && $couier->store_id) {
                    $store = $this->storeRepository->find($couier->store_id);
                }
            }

            if ($store) {
                $factor = $store->getCurrencyFactor();
            }

            $courierData['commission_amount'] = CurrencyHelper::priceToUnsignedBigInt($courierData['commission_amount'], $factor);
        }

        $couier = $this->CouierRepository->update($id, $courierData)->refresh();
        // Create National Identity
        if (isset($data['nationalID'])) {
            $nationalIDData = $data['nationalID'];
            if (request()->hasFile('nationalID.front_image')) {
                $nationalIDData['front_image'] = $this->upload(
                    request(),
                    'nationalID.front_image',
                    'uploads/national_ids',
                    'public'
                );
            }
            if (request()->hasFile('nationalID.back_image')) {
                $nationalIDData['back_image'] = $this->upload(
                    request(),
                    'nationalID.back_image',
                    'uploads/national_ids',
                    'public'
                );
            }
            $couier->nationalIdentity()->update($nationalIDData);
        }

        // Update or Create Vehicle
        if (isset($data['vehicle'])) {
            $vehicleData = $data['vehicle'];
            if (isset($data['vehicle']['car_license']) && request()->hasFile('vehicle.car_license')) {
                $vehicleData['car_license'] = $this->upload(
                    request(),
                    'vehicle.car_license',
                    'uploads/car_licenses',
                    'public'
                );
            }
            // If a vehicle exists, update it; otherwise create a new one via the relation
            if ($couier->vehicle) {
                $couier->vehicle()->update($vehicleData);
            } else {
                $couier->vehicle()->create($vehicleData);
            }
        }

        // Create Attachments
        if (isset($data['attachment'])) {
            foreach ($data['attachment'] as $attachmentData) {
                if (request()->hasFile('attachment.' . array_search($attachmentData, $data['attachment']) . '.path')) {
                    $attachmentData['path'] = $this->upload(
                        request(),
                        'attachment.' . array_search($attachmentData, $data['attachment']) . '.path',
                        'uploads/attachments',
                        'public'
                    );
                }
                $couier->attachments()->create($attachmentData);
            }
        }

        // Attach Zones
        if (isset($data['zones_to_cover'])) {
            $couier->zonesToCover()->attach($data['zones_to_cover']);
        }

if (isset($data['address'])) {
    $addressData = $data['address'];

    // Separate translatable fields
    $translatedFields = [];
    if (isset($addressData['address_line_1'])) {
        $translatedFields['address_line_1'] = $addressData['address_line_1'];
        unset($addressData['address_line_1']);
    }
    if (isset($addressData['address_line_2'])) {
        $translatedFields['address_line_2'] = $addressData['address_line_2'];
        unset($addressData['address_line_2']);
    }

    if ($couier->address) {
        // Update main address table
        $couier->address()->update($addressData);

        // Update translations
        if (!empty($translatedFields)) {
            $couier->address->update($translatedFields);
        }
    } else {
        // Create new address with translations
        $couierAddress = $couier->address()->create($addressData);
        if (!empty($translatedFields)) {
            $couierAddress->update($translatedFields);
        }
    }
}

        return $couier->refresh();
    }

    public function deleteCouier(int $id): bool
    {
        return $this->CouierRepository->delete($id);
    }
    public function stats(): array
    {
        $cacheKey = 'couier_stats';

        return Cache::remember($cacheKey, now()->addMinutes(10), function () {
            $totalCouiers = Couier::count();
            $activeCouiers = Couier::where('status', \App\Enums\UserStatusEnum::ACTIVE)->count();

            $availableCouiers = Couier::where('avaliable_status', \App\Enums\CouierAvaliableStatusEnum::AVAILABLE)->count();

            $averageRate = Couier::where('status', \App\Enums\UserStatusEnum::ACTIVE)
                ->avg('avg_rate');

            $totalOrders = \Modules\Order\Models\Order::whereNotNull('couier_id')->count();

            return [
                'total_couiers' => $totalCouiers,
                'active_couiers' => $activeCouiers,
                'available_couiers' => $availableCouiers,
                'average_rate' => round($averageRate, 2),
                'total_orders' => $totalOrders,
            ];
        });
    }
    public function getHome()
    {
        $courier = auth('sanctum')->user();
        $currentOrder = \Modules\Order\Models\Order::where('couier_id', $courier->id)
            ->whereHas('courierOrderAssignment', function ($q){
                $q->whereIn('status', ['accepted', 'in_transit']);
            })
            ->with(['store', 'user'])
            ->first();

        $balance = \Modules\Balance\Models\Balance::where('balanceable_id', $courier->id)
            ->where('balanceable_type', get_class($courier))
            ->first();

        $availableBalance = $balance ? (float) $balance->available_balance : 0.0;

        $shiftData = $this->getShiftData();
        $zoneData = $this->getZoneWithStoresHavingOrders();

        return [
            "first_name" => $courier->first_name,
            "last_name" => $courier->last_name,
            "name" => $courier->first_name . " " . $courier->last_name,
            "email" => $courier->email,
            "phone" => $courier->phone,
            "avatar" => $courier->avatar ? asset('storage/' . $courier->avatar) : null,

            'avaliable_status' => $courier->avaliable_status,
            'wallet_balance' => $availableBalance,
            'currency_factor' => $courier->store->getCurrencyFactor(),
            'currency_code' => $courier->store->getCurrencyCode(),
            'zone' => $zoneData,
            'current_order' => $currentOrder ? new FullOrderResource($currentOrder->courierOrderAssignment) : null,
            'shift' => $shiftData,
        ];
    }
    private function getZoneWithStoresHavingOrders()
    {
        // Get zones that have stores with orders
        $zones = \Modules\Zone\Models\Zone::with(['stores' => function($query) {
                $query->withCount('orders')
                      ->having('orders_count', '>', 0)
                      ->orderBy('orders_count', 'desc');
            }])
            ->whereHas('stores.orders')
            ->get();

        return ZoneResource::collection($zones);
    }

private function getShiftData(): array
{
    $courier = auth('sanctum')->user();
    $current_shift = $courier->activeShifts()->first();

    // Handle location data
    $location = $this->getLocationData();

    // Get current shift data
    $currentShiftData = $this->getCurrentShiftData($current_shift);

    // Get next shift data (exclude current shift if it exists)
    $nextShiftData = $this->getNextShiftData($courier, $current_shift);

    // Calculate remaining time
    $remainingData = $this->calculateRemainingTime($nextShiftData);

    return [
        'location' => $location,
        'current_shift' => $currentShiftData,
        'next_shift' => !$currentShiftData ? $nextShiftData : null,
        'remaining_time' => !$currentShiftData ? $remainingData : null,
    ];
}

private function getLocationData(): ?string
{
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

    return $location;
}

private function getCurrentShiftData($current_shift): ?array
{
    if (!$current_shift) {
        return null;
    }

    // Get shift template info
    $shiftTemplate = null;
    if ($current_shift->shift_template_id) {
        $template = \Modules\Couier\Models\ShiftTemplate::find($current_shift->shift_template_id);
        if ($template) {
            $shiftTemplate = [
                'id' => $template->id,
                'name' => $template->name,
                'is_active' => $template->is_active,
                'is_flexible' => $template->is_flexible,
            ];
        }
    }

    // Calculate total hours if end_time exists
    $totalHours = 0;
    if ($current_shift->start_time && $current_shift->end_time) {
        $totalHours = $current_shift->start_time->diffInHours($current_shift->end_time - $current_shift->break_duration);
    }

    return [
        'id' => $current_shift->id,
        'shift_template_id' => $current_shift->shift_template_id,
        'day_of_week' => $current_shift->start_time ?  WorkingDayEnum::label($current_shift->start_time->dayOfWeek) : null,
        'start_time' => $current_shift->start_time?->toDateTimeString(),
        'end_time' => $current_shift->end_time?->toDateTimeString(),
        'total_hours' => $totalHours,
        'break_duration' => $current_shift->break_duration,
        'formatted_time' => $this->formatShiftTime($current_shift->start_time, $current_shift->end_time),
        'shift_template' => $shiftTemplate,
    ];
}

private function getNextShiftData($courier, $current_shift = null): ?array
{
    $now = now();

    // Get active shift templates with shift template data
    $shiftTemplates = $courier->activeShiftTemplates()
        ->with('shiftTemplate')
        ->get();

    if ($shiftTemplates->isEmpty()) {
        return null;
    }

    // Check for next 7 days
    for ($i = 0; $i < 7; $i++) {
        $checkDate = $now->copy()->addDays($i);
        $dayOfWeek = $checkDate->dayOfWeek;

        foreach ($shiftTemplates as $templateAssignment) {
            $schedule = $templateAssignment->weekly_schedule;

            foreach ($schedule as $daySchedule) {
                // Skip if this is an off day or has no start/end time
                if ($daySchedule['is_off_day'] || empty($daySchedule['start_time']) || empty($daySchedule['end_time'])) {
                    continue;
                }

                if ($daySchedule['day_of_week'] == $dayOfWeek) {
                    $startTime = \Carbon\Carbon::parse($daySchedule['start_time']);
                    $endTime = \Carbon\Carbon::parse($daySchedule['end_time']);

                    // Skip if start and end time are the same
                    if ($startTime->eq($endTime)) {
                        continue;
                    }

                    // Combine date and time
                    $shiftStart = $checkDate->copy()->setTime($startTime->hour, $startTime->minute);
                    $shiftEnd = $checkDate->copy()->setTime($endTime->hour, $endTime->minute);

                    // Skip if this is the current shift
                    if ($current_shift && $current_shift->start_time && $current_shift->start_time->isSameDay($shiftStart)) {
                        continue;
                    }

                    // Check if this shift is in the future (not started yet)
                    if ($shiftStart->isFuture()) {
                        // Get shift template details
                        $shiftTemplate = null;
                        if ($templateAssignment->shiftTemplate) {
                            $shiftTemplate = [
                                'id' => $templateAssignment->shiftTemplate->id,
                                'name' => $templateAssignment->shiftTemplate->name,
                                'is_active' => $templateAssignment->shiftTemplate->is_active,
                                'is_flexible' => $templateAssignment->shiftTemplate->is_flexible,
                            ];
                        }

                        return [
                            'id' => $templateAssignment->id,
                            'shift_template_id' => $templateAssignment->shift_template_id,
                            'day_of_week' => WorkingDayEnum::label($dayOfWeek),
                            'start_time' => $shiftStart->toDateTimeString(),
                            'end_time' => $shiftEnd->toDateTimeString(),
                            'total_hours' => $daySchedule['total_hours'] ?? 0,
                            'break_duration' => $daySchedule['break_duration'] ?? 0,
                            'formatted_time' => $this->formatShiftTime($shiftStart, $shiftEnd),
                            'shift_template' => $shiftTemplate,
                        ];
                    }
                }
            }
        }
    }

    return null;
}

private function calculateRemainingTime($nextShiftData)
{
    if (!$nextShiftData || empty($nextShiftData['start_time'])) {
        return null;
    }

    $now = now();
    $shiftStartTime = Carbon::parse($nextShiftData['start_time']);

    if ($shiftStartTime->isPast()) {
        return null;
    }

    $hours = $now->diffInHours($shiftStartTime);
    $minutes = $now->copy()->addHours($hours)->diffInMinutes($shiftStartTime);

    return [
        'hours' => $hours,
        'minutes' => $minutes,
        'total_minutes' => $now->diffInMinutes($shiftStartTime),
        'formatted' => sprintf('%02dh %02dm', $hours, $minutes)
    ];
}

private function formatShiftTime($start, $end): string
{
    if (!$start || !$end) {
        return '';
    }

    $startTime = $start instanceof \Carbon\Carbon ? $start : \Carbon\Carbon::parse($start);
    $endTime = $end instanceof \Carbon\Carbon ? $end : \Carbon\Carbon::parse($end);

    // Return empty if times are the same
    if ($startTime->eq($endTime)) {
        return '';
    }

    $totalHours = $startTime->diffInHours($endTime);

    return sprintf("(%dh) %s - %s",
        $totalHours,
        $startTime->format('H:i'),
        $endTime->format('H:i')
    );
}
}
