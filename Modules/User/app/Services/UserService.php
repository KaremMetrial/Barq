<?php

namespace Modules\User\Services;

use Illuminate\Support\Str;
use Modules\User\Models\User;
use App\Traits\FileUploadTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
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

            // Check if a soft-deleted user exists with the same email or phone
            $existingUser = User::withTrashed()
                ->where('email', $filteredData['email'] ?? null)
                ->orWhere('phone', $filteredData['phone'] ?? null)
                ->first();

            if ($existingUser && $existingUser->trashed()) {
                // Restore the soft-deleted user
                $existingUser->restore();

                // Update the user data (hash password if provided)
                $updateData = $filteredData;
                if (isset($updateData['password'])) {
                    $updateData['password'] = Hash::make($updateData['password']);
                }

                $user = $this->UserRepository->update($existingUser->id, $updateData);

                // Delete existing addresses to recreate them
                $user->addresses()->delete();
            } else {
                $refCode = request()->referral_code ?? null;
                // Hash password for new user
                if (isset($filteredData['password'])) {
                    $filteredData['password'] = Hash::make($filteredData['password']);
                }
                $filteredData['referral_code'] =Str::upper(Str::random(8));

                $user = $this->UserRepository->create($filteredData);

                if($refCode){
                    $referrer = User::where('referral_code',$refCode)->first();
                    if($referrer){
                        $user->update(['referral_id'=>$referrer->id]);
                        $referrer->increment('loyalty_points',100);
                    }
                }
            }

            if ($addressData) {
                $user->addresses()->create($addressData);
            }
            return $user->refresh();
        });
    }
}
