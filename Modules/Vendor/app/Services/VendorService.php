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

class VendorService
{
    use FileUploadTrait;
    public function __construct(
        protected VendorRepository $VendorRepository
    ) {}

    public function getAllVendors(): Collection
    {
        return $this->VendorRepository->all();
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
            return $this->VendorRepository->create($data);
        });
    }

    public function getVendorById(int $id): ?Vendor
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
            return $this->VendorRepository->update($id, $data);
        });
    }

    public function deleteVendor(int $id): bool
    {
        return $this->VendorRepository->delete($id);
    }
    public function login(array $data)
    {
        $vendor = $this->VendorRepository->firstWhere([
            'email' => $data['email']
        ]);
        if (! $vendor || ! Hash::check($data['password'], $vendor->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }
        $token = $vendor->createToken('vendor')->plainTextToken;
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
