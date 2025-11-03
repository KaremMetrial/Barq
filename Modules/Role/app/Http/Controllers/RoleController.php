<?php

namespace Modules\Role\Http\Controllers;

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
        $terminals = $this->RoleService->getAllRoles();
        return $this->successResponse([
            "terminals" => RoleResource::collection($terminals)
        ], __('message.success'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateRoleRequest $request): JsonResponse
    {
        $terminal = $this->RoleService->createRole($request->all());
        return $this->successResponse([
            'terminal' => new RoleResource($terminal)
        ], __('message.success'));
    }

    /**
     * Show the specified resource.
     */
    public function show(int $id): JsonResponse
    {
        $terminal = $this->RoleService->getRoleById($id);
        return $this->successResponse([
            'terminal' => new RoleResource($terminal)
        ], __('message.success'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRoleRequest $request, int $id): JsonResponse
    {
        $terminal = $this->RoleService->updateRole($id, $request->all());
        return $this->successResponse([
            'terminal' => new RoleResource($terminal)
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
}
