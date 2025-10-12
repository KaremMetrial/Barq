<?php

namespace Modules\Vendor\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\JsonResponse;
use Modules\Vendor\Models\Vendor;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Modules\Vendor\Services\VendorService;
use Modules\Vendor\Http\Requests\LoginRequest;
use Modules\Vendor\Http\Resources\VendorResource;
use Modules\Vendor\Http\Requests\CreateVendorRequest;
use Modules\Vendor\Http\Requests\UpdateVendorRequest;
use Modules\Vendor\Http\Requests\UpdatePasswordRequest;

class VendorController extends Controller
{
    use ApiResponse;

    // Inject the VendorService to handle business logic
    public function __construct(protected VendorService $vendorService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $vendors = $this->vendorService->getAllVendors();
        return $this->successResponse([
            "vendors" => VendorResource::collection($vendors)
        ], __('message.success'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateVendorRequest $request): JsonResponse
    {
        $vendor = $this->vendorService->createVendor($request->validated());
        return $this->successResponse([
            'vendor' => new VendorResource($vendor)
        ], __('message.success'));
    }

    /**
     * Show the specified resource.
     */
    public function show(int $id): JsonResponse
    {
        $vendor = $this->vendorService->getVendorById($id);
        return $this->successResponse([
            'vendor' => new VendorResource($vendor)
        ], __('message.success'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateVendorRequest $request, int $id): JsonResponse
    {
        $vendor = $this->vendorService->updateVendor($id, $request->validated());
        return $this->successResponse([
            'vendor' => new VendorResource($vendor)
        ], __('message.success'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $isDeleted = $this->vendorService->deleteVendor($id);
        return $this->successResponse(null, __('message.success'));
    }
    public function login(LoginRequest $request): JsonResponse
    {
        $vendor = $this->vendorService->login($request->validated());
        return $this->successResponse([
            'vendor' => new VendorResource($vendor['vendor']),
            'token' => $vendor['token'],
        ], __('message.success'));
    }
    public function logout(Request $request): JsonResponse
    {
        $vendor = $this->vendorService->logout($request);

        return $this->successResponse(null, __('message.success'));
    }
    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $vendor = $this->vendorService->updatePassword($request->validated());
        return $this->successResponse(null, __('message.success'));
    }
}
