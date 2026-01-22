<?php

namespace Modules\Vendor\Services;

use App\Traits\FileUploadTrait;
use Modules\Vendor\Models\Vendor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\Vendor\Repositories\VendorRepository;
use App\Models\Role;

class VendorService
{
    use FileUploadTrait;
    public function __construct(
        protected VendorRepository $VendorRepository
    ) {}

    public function getAllVendors($filters = [])
    {
        return $this->VendorRepository->paginate($filters, $perPage = 10,[
            'store.translations',
            'store.address.translations',
            'store.address.zone.translations',
        ]);
    }

    public function createVendor(array $data): ?Vendor
    {
        return DB::transaction(function () use ($data) {
            $data['logo'] = $this->upload(
                request(),
                'logo',
                'uploads/logos',
                'public'
            );
            $data['cover_image'] = $this->upload(
                request(),
                'cover_image',
                'uploads/cover_images',
                'public'
            );
            $data = array_filter($data, fn($value) => !blank($value));
            $vendor = $this->VendorRepository->create($data);
            if (isset($data['role'])) {
                // $roleIds = explode(',', $data['role_ids']);
                // $vendor->roles()->sync($roleIds);
                $vendor->syncRoles($data['role']);
            } else {
                $vendor->assignRole('vendor');
            }
            return $vendor;
        });
    }

    public function getVendorById(int $id)
    {
        return $this->VendorRepository->find($id);
    }

    public function updateVendor(int $id, array $data): ?Vendor
    {
        return DB::transaction(function () use ($data, $id) {
            $data['logo'] = $this->upload(
                request(),
                'logo',
                'uploads/logos',
                'public'
            );
            $data['cover_image'] = $this->upload(
                request(),
                'cover_image',
                'uploads/cover_images',
                'public'
            );
            $data = array_filter($data, fn($value) => !blank($value));
            $vendor = $this->VendorRepository->update($id, $data);

            return $vendor;
        });
    }

    public function deleteVendor(int $id): bool
    {
        return $this->VendorRepository->delete($id);
    }
    public function login(array $data)
    {
        $vendor = $this->VendorRepository->firstWhere([
            'email' => $data['email'],
            'is_active' => true,
        ]);
        if (! $vendor || ! Hash::check($data['password'], $vendor->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }
        $vendor->update([
            'last_login' => now(),
        ]);
        $newToken = $vendor->createToken('token',['vendor']);
        $newToken->accessToken->fcm_device = request()->input('fcm_device');
        $newToken->accessToken->save();
        $token = $newToken->plainTextToken;

        return [
            'vendor' => $vendor,
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
        $vendor = request()->user('vendor');
        $vendor->update([
            'password' => Hash::make($data['password'])
        ]);

        return $vendor;
    }
}
