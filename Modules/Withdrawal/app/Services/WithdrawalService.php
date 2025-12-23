<?php

namespace Modules\Withdrawal\Services;

use App\Traits\FileUploadTrait;
use Modules\Withdrawal\Models\Withdrawal;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Collection;
use Modules\Withdrawal\Repositories\WithdrawalRepository;

class WithdrawalService
{
    use FileUploadTrait;
    public function __construct(
        protected WithdrawalRepository $WithdrawalRepository
    ) {}

    public function getAllWithdrawals($filters = [])
    {
        return $this->WithdrawalRepository->paginate($filters);
    }
    public function createWithdrawal(array $data): ?Withdrawal
    {
        $data['icon'] = $this->upload(request(), 'icon', 'icons/Withdrawals');
        $data = array_filter($data, fn($value) => !blank($value));
        return $this->WithdrawalRepository->create($data)->refresh();
    }
    public function getWithdrawalById(int $id)
    {
        return $this->WithdrawalRepository->find($id);
    }
    public function updateWithdrawal(int $id, array $data)
    {
        $data['icon'] = $this->upload(request(), 'icon', 'icons/Withdrawals');
        $data = array_filter($data, fn($value) => !blank($value));
        return $this->WithdrawalRepository->update($id, $data)->refresh();
    }
    public function deleteWithdrawal(int $id): bool
    {
        return $this->WithdrawalRepository->delete($id);
    }
}
