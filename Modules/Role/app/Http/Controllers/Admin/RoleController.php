<?php

namespace Modules\Role\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Modules\Role\Http\Requests\CreateRoleRequest;
use Modules\Role\Http\Requests\UpdateRoleRequest;
use Modules\Role\Http\Resources\RoleResource;
use Modules\Role\Services\RoleService;

class RoleController extends Controller
{
    use ApiResponse;

    public function __construct(protected RoleService $RoleService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $roles = $this->RoleService->getAllRoles();
        return $this->successResponse([
            "roles" => RoleResource::collection($roles)
        ], __('message.success'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateRoleRequest $request): JsonResponse
    {
        $role = $this->RoleService->createRole($request->all());
        return $this->successResponse([
            'role' => new RoleResource($role)
        ], __('message.success'));
    }

    /**
     * Show the specified resource.
     */
    public function show(int $id): JsonResponse
    {
        $role = $this->RoleService->getRoleById($id);
        return $this->successResponse([
            'role' => new RoleResource($role),
            'permissions' => $role->permissions->pluck('name')
        ], __('message.success'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRoleRequest $request, int $id): JsonResponse
    {
        $role = $this->RoleService->updateRole($id, $request->all());
        return $this->successResponse([
            'role' => new RoleResource($role)
        ], __('message.success'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $isDeleted = $this->RoleService->deleteRole($id);
        return $this->successResponse(null, __('message.success'));
    }
    public function permissions(): JsonResponse
    {
        $permissions = $this->RoleService->getAllPermissions();
        return $this->successResponse([
            'permissions' => $permissions
        ], __('message.success'));
    }
}
