<?php

namespace Modules\PosTerminal\Services;

use App\Traits\FileUploadTrait;
use Illuminate\Support\Facades\DB;
use Modules\PosTerminal\Models\PosTerminal;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Collection;
use Modules\PosTerminal\Repositories\PosTerminalRepository;

class PosTerminalService
{
    use FileUploadTrait;
    public function __construct(
        protected PosTerminalRepository $PosTerminalRepository
    ) {}

    public function getAllPosTerminals(): Collection
    {
        return $this->PosTerminalRepository->all();
    }

    public function createPosTerminal(array $data): ?PosTerminal
    {
        return DB::transaction(function () use ($data) {
            $data = array_filter($data, fn($value) => !blank($value));
            return $this->PosTerminalRepository->create($data);
        });
    }

    public function getPosTerminalById(int $id): ?PosTerminal
    {
        return $this->PosTerminalRepository->find($id, ['store']);
    }

    public function updatePosTerminal(int $id, array $data): ?PosTerminal
    {
        return DB::transaction(function () use ($data, $id) {
            $data = array_filter($data, fn($value) => !blank($value));
            return $this->PosTerminalRepository->update($id, $data);
        });

    }

    public function deletePosTerminal(int $id): bool
    {
        return $this->PosTerminalRepository->delete($id);
    }
}
