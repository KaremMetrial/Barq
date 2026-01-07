<?php

namespace Modules\Role\Services;

use App\Traits\FileUploadTrait;
use Illuminate\Support\Facades\DB;
use Modules\Role\Models\Role;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Collection;
use Modules\Role\Repositories\RoleRepository;

class RoleService
{
    use FileUploadTrait;
    public function __construct(
        protected RoleRepository $RoleRepository
    ) {}

    public function getAllRoles(): Collection
    {
        return $this->RoleRepository->all();
    }

    public function createRole(array $data): ?Role
    {
        return DB::transaction(function () use ($data) {
            $data = array_filter($data, fn($value) => !blank($value));
            $role =  $this->RoleRepository->create($data);
            $role->syncPermissions($data['permissions']);
            return $role;
        });
    }

    public function getRoleById(int $id)
    {
        return $this->RoleRepository->find($id);
    }

    public function updateRole(int $id, array $data)
    {
        return DB::transaction(function () use ($data, $id) {
            $data = array_filter($data, fn($value) => !blank($value));
            $role = $this->RoleRepository->update($id, $data);
            $role->syncPermissions($data['permissions']);
            return $role;
        });

    }

    public function deleteRole(int $id): bool
    {
        return $this->RoleRepository->delete($id);
    }
    public function getAllPermissions(): array
    {
        return Cache::rememberForever('permissions_list', function () {
            $guard = request()->input('guard_name', 'admin');

            return DB::table('permissions')
                ->where('guard_name', $guard)
                ->pluck('name')
                ->toArray();
        });
    }
}
