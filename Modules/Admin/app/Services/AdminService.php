<?php

namespace Modules\Admin\Services;

use Illuminate\Http\Request;
use App\Traits\FileUploadTrait;
use Modules\Admin\Models\Admin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use Modules\Admin\Repositories\AdminRepository;

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
        public function login(array $data)
    {
        $admin = $this->AdminRepository->firstWhere([
            'email' => $data['email']
        ]);
        if (! $admin || ! Hash::check($data['password'], $admin->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }
        $newToken = $admin->createToken('token',['admin']);
        $newToken->accessToken->fcm_device = request()->input('fcm_device');
        $newToken->accessToken->save();
        $token = $newToken->plainTextToken;

        return [
            'admin' => $admin,
            'token' => $token
        ];
    }
    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        return true;
    }
    public function updatePassword(array $data)
    {
        $admin = request()->user('admin');
        $admin->update([
            'password' => Hash::make($data['password'])
        ]);

        return $admin;
    }

}
