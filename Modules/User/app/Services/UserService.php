<?php

namespace Modules\User\Services;

use Modules\User\Models\User;
use App\Traits\FileUploadTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Collection;
use Modules\User\Repositories\UserRepository;

class UserService
{
    use FileUploadTrait;

    public function __construct(
        protected UserRepository $UserRepository
    ) {}

    public function getAllUsers(): Collection
    {
        return $this->UserRepository->all();
    }
    public function createUser(array $data): ?User
    {
        $data = array_filter($data, fn($value) => !blank($value));
        return $this->UserRepository->create($data)->refresh();
    }
    public function getUserById(int $id)
    {
        return $this->UserRepository->find($id);
    }
    public function updateUser(int $id, array $data)
    {
        $data = array_filter($data, fn($value) => !blank($value));

        // Handle password hashing if provided
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        // Handle avatar upload if provided
        if (isset($data['avatar']) && $data['avatar'] instanceof \Illuminate\Http\UploadedFile) {
            $data['avatar'] = $this->upload(request(), 'avatar', 'avatars');
        }

        return $this->UserRepository->update($id, $data)->refresh();
    }
    public function deleteUser(int $id): bool
    {
        return $this->UserRepository->delete($id);
    }
    public function registerUser(array $data): ?User
    {
        return DB::transaction(function () use ($data) {
            $filteredData = array_filter($data, fn($value) => !blank($value));

            $addressData = $filteredData['address'] ?? null;
            unset($filteredData['address']);

            $user = $this->UserRepository->create($filteredData);

            if ($addressData) {
                $user->addresses()->create($addressData);
            }
            return $user->refresh();
        });
    }
}
