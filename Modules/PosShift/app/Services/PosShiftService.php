<?php

namespace Modules\PosShift\Services;

use App\Traits\FileUploadTrait;
use Illuminate\Support\Facades\DB;
use Modules\PosShift\Models\PosShift;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Collection;
use Modules\PosShift\Repositories\PosShiftRepository;

class PosShiftService
{
    use FileUploadTrait;
    public function __construct(
        protected PosShiftRepository $PosShiftRepository
    ) {}

    public function getAllPosShifts(): Collection
    {
        return $this->PosShiftRepository->all();
    }

    public function createPosShift(array $data): ?PosShift
    {
        return DB::transaction(function () use ($data) {
            $data = array_filter($data, fn($value) => !blank($value));
            return $this->PosShiftRepository->create($data);
        });
    }

    public function getPosShiftById(int $id): ?PosShift
    {
        return $this->PosShiftRepository->find($id, ['vendor', 'posTerminal']);
    }

    public function updatePosShift(int $id, array $data): ?PosShift
    {
        return DB::transaction(function () use ($data, $id) {
            $data = array_filter($data, fn($value) => !blank($value));
            return $this->PosShiftRepository->update($id, $data);
        });
    }

    public function deletePosShift(int $id): bool
    {
        return $this->PosShiftRepository->delete($id);
    }
}
