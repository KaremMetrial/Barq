<?php

namespace Modules\DeliveryInstruction\Services;

use Illuminate\Support\Facades\DB;
use Modules\DeliveryInstruction\Models\DeliveryInstruction;
use Illuminate\Database\Eloquent\Collection;
use Modules\DeliveryInstruction\Repositories\DeliveryInstructionRepository;

class DeliveryInstructionService
{
    public function __construct(
        protected DeliveryInstructionRepository $DeliveryInstructionRepository
    ) {}

    public function getAllDeliveryInstructions(): Collection
    {
        return $this->DeliveryInstructionRepository->all();
    }

    public function createDeliveryInstruction(array $data): ?DeliveryInstruction
    {
        return DB::transaction(function () use ($data) {
            $data = array_filter($data, fn($value) => !blank($value));
            return $this->DeliveryInstructionRepository->create($data);
        });
    }

    public function getDeliveryInstructionById(int $id): ?DeliveryInstruction
    {
        return $this->DeliveryInstructionRepository->find($id);
    }

    public function updateDeliveryInstruction(int $id, array $data): ?DeliveryInstruction
    {
        return DB::transaction(function () use ($data, $id) {
            $data = array_filter($data, fn($value) => !blank($value));
            return $this->DeliveryInstructionRepository->update($id, $data);
        });
    }

    public function deleteDeliveryInstruction(int $id): bool
    {
        return $this->DeliveryInstructionRepository->delete($id);
    }
}
