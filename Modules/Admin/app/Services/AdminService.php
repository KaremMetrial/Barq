<?php

namespace Modules\Admin\Services;

use App\Traits\FileUploadTrait;
use Illuminate\Database\Eloquent\Collection;
use Modules\Admin\Models\Admin;
use Modules\Admin\Repositories\AdminRepository;
use Illuminate\Support\Facades\Cache;

class AdminService
{
    use FileUploadTrait;
    public function __construct(
        protected AdminRepository $AdminRepository
    ) {}

    public function getAllAdmins(): Collection
    {
        return $this->AdminRepository->all();
    }

    public function createAdmin(array $data): ?Admin
    {
        if (request()->hasFile('avatar')) {
            $data['avatar'] = $this->upload(
                request(),
                'avatar',
                'uploads/avatars',
                'public'
            );
        }
        $data = array_filter($data, fn($value) => !blank($value));
        return $this->AdminRepository->create($data);
    }

    public function getAdminById(int $id)
    {
        return $this->AdminRepository->find($id);
    }

    public function updateAdmin(int $id, array $data)
    {
        if (request()->hasFile('avatar')) {
            $data['avatar'] = $this->upload(
                request(),
                'avatar',
                'uploads/avatars',
                'public'
            );
        }
        $data = array_filter($data, fn($value) => !blank($value));
        return $this->AdminRepository->update($id, $data);
    }

    public function deleteAdmin(int $id): bool
    {
        return $this->AdminRepository->delete($id);
    }
}
