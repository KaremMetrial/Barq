<?php

namespace Modules\Couier\Services;

use App\Helpers\CurrencyHelper;
use App\Models\Attachment;
use App\Traits\FileUploadTrait;
use App\Models\NationalIdentity;
use Modules\Couier\Models\Couier;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Collection;
use Modules\Couier\Repositories\CouierRepository;
use Illuminate\Support\Facades\DB;
use Modules\Store\Repositories\StoreRepository;

class CouierService
{
    use FileUploadTrait;
    public function __construct(
        protected CouierRepository $CouierRepository,
        protected StoreRepository $storeRepository
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
}
