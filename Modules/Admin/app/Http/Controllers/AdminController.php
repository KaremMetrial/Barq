<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Modules\Admin\Http\Requests\CreateAdminRequest;
use Modules\Admin\Http\Requests\UpdateAdminRequest;
use Modules\Admin\Http\Resources\AdminResource;
use Modules\Admin\Services\AdminService;

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
}
