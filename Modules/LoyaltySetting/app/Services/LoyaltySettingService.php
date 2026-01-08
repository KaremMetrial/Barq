<?php

namespace Modules\LoyaltySetting\Services;

use App\Traits\FileUploadTrait;
use Illuminate\Support\Facades\DB;
use Modules\LoyaltySetting\Models\LoyaltySetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Collection;
use Modules\LoyaltySetting\Repositories\LoyaltySettingRepository;
use App\Helpers\CurrencyHelper;

class LoyaltySettingService
{
    public function __construct(
        protected LoyaltySettingRepository $LoyaltySettingRepository
    ) {}

    public function getAllLoyaltySettings(): Collection
    {
        return $this->LoyaltySettingRepository->allWithTranslations();
    }

    public function createLoyaltySetting(array $data): ?LoyaltySetting
    {
        return DB::transaction(function () use ($data) {
            $currencyFactor = $data['currency_factor'] ?? null;
            $data = array_filter($data, fn($value) => !blank($value));
            if ($currencyFactor) {
              $data['min_order_for_earn'] = CurrencyHelper::toMinorUnits($data['min_order_for_earn'], $currencyFactor);
            }

            return $this->LoyaltySettingRepository->create($data);
        });
    }

    public function getLoyaltySettingById(int $id): ?LoyaltySetting
    {
        return $this->LoyaltySettingRepository->find($id);
    }

    public function updateLoyaltySetting(int $id, array $data): ?LoyaltySetting
    {
        return DB::transaction(function () use ($data, $id) {
            $currencyFactor = $data['currency_factor'] ?? null;
            $data = array_filter($data, fn($value) => !blank($value));
            if ($currencyFactor) {
              $data['min_order_for_earn'] = CurrencyHelper::toMinorUnits($data['min_order_for_earn'], $currencyFactor);
            }
            return $this->LoyaltySettingRepository->update($id, $data);
        });

    }

    public function deleteLoyaltySetting(int $id): bool
    {
        return $this->LoyaltySettingRepository->delete($id);
    }
}
