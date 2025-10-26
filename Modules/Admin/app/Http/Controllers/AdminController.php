<?php

namespace Modules\Admin\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Modules\Admin\Services\AdminService;
use Modules\Admin\Http\Requests\LoginRequest;
use Modules\Admin\Http\Resources\AdminResource;
use Modules\Admin\Http\Requests\CreateAdminRequest;
use Modules\Admin\Http\Requests\UpdateAdminRequest;
use Modules\Admin\Http\Requests\UpdatePasswordRequest;

class AdminController extends Controller
{
    use ApiResponse;

    public function __construct(protected AdminService $adminService) {}

    /**
     * Display a listing of the admins.
     */
    public function index(): JsonResponse
    {
        $admins = $this->adminService->getAllAdmins();

        return $this->successResponse([
            "admins" => AdminResource::collection($admins),
        ], __("message.success"));
    }

    /**
     * Store a newly created admin.
     */
    public function store(CreateAdminRequest $request): JsonResponse
    {
        $admin = $this->adminService->createAdmin($request->all());

        return $this->successResponse([
            "admin" => new AdminResource($admin),
        ], __("message.success"));
    }

    /**
     * Display the specified admin.
     */
    public function show(int $id): JsonResponse
    {
        $admin = $this->adminService->getAdminById($id);

        return $this->successResponse([
            "admin" => new AdminResource($admin),
        ], __("message.success"));
    }

    /**
     * Update the specified admin.
     */
    public function update(UpdateAdminRequest $request, int $id): JsonResponse
    {
        $admin = $this->adminService->updateAdmin($id, $request->all());

        return $this->successResponse([
            "admin" => new AdminResource($admin),
        ], __("message.success"));
    }

    /**
     * Remove the specified admin from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->adminService->deleteAdmin($id);

        return $this->successResponse(null, __("message.success"));
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $admin = $this->adminService->login($request->validated());
        return $this->successResponse([
            'admin' => new AdminResource($admin['admin']),
            'token' => $admin['token'],
        ], __('message.success'));
    }
    public function logout(Request $request): JsonResponse
    {
        $vendor = $this->adminService->logout($request);

        return $this->successResponse(null, __('message.success'));
    }
    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $vendor = $this->adminService->updatePassword($request->validated());
        return $this->successResponse(null, __('message.success'));
    }
}
