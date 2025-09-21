<?php

namespace Modules\WorkingDay\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\WorkingDay\Models\WorkingDay;
use Modules\WorkingDay\Repositories\WorkingDayRepository;
use Illuminate\Support\Facades\Cache;

class WorkingDayService
{
    public function __construct(
        protected WorkingDayRepository $WorkingDayRepository
    ) {}

    public function getAllWorkingDays(): Collection
    {
        return $this->WorkingDayRepository->all();
    }

    public function createWorkingDay(array $data): ?WorkingDay
    {
        return $this->WorkingDayRepository->create($data);
    }

    public function getWorkingDayById(int $id): ?WorkingDay
    {
        return $this->WorkingDayRepository->find($id);
    }

    public function updateWorkingDay(int $id, array $data): ?WorkingDay
    {
        return $this->WorkingDayRepository->update($id, $data);
    }

    public function deleteWorkingDay(int $id): bool
    {
        return $this->WorkingDayRepository->delete($id);
    }
}
