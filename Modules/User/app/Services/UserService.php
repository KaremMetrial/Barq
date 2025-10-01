<?php

namespace Modules\User\Services;

use Modules\User\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Collection;
use Modules\User\Repositories\UserRepository;

class UserService
{
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
        return $this->UserRepository->update($id, $data)->refresh();
    }
    public function deleteUser(int $id): bool
    {
        return $this->UserRepository->delete($id);
    }
    public function registerUser(array $data): ?User
    {
        $data = array_filter($data, fn($value) => !blank($value));
        return $this->UserRepository->create($data)->refresh();
    }
}
