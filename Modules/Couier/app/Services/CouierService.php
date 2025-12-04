<?php

namespace Modules\Couier\Services;

use App\Models\Attachment;
use App\Traits\FileUploadTrait;
use App\Models\NationalIdentity;
use Modules\Couier\Models\Couier;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Collection;
use Modules\Couier\Repositories\CouierRepository;

class CouierService
{
    use FileUploadTrait;
    public function __construct(
        protected CouierRepository $CouierRepository
    ) {}

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
        $courierData = array_filter($data['courier'], fn($value) => !blank($value));

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
                $attachmentData['_type'] = Couier::class;
                Attachment::create($attachmentData);
            }
        }

        // Attach Zones
        if (isset($data['zones_to_cover'])) {
            $couier->zones()->sync($data['zones_to_cover']);
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
        $courierData = array_filter($courierData, fn($value) => !blank($value));
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

        // Create Vehicle
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
            $vehicleData['courier_id'] = $couier->id;
            $couier->vehicle()->create($vehicleData);
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
            $couier->zones()->attach($data['zones_to_cover']);
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
