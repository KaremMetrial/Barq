<?php

namespace Modules\AddOn\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Modules\AddOn\Http\Requests\CreateAddOnRequest;
use Modules\AddOn\Http\Requests\UpdateAddOnRequest;
use Modules\AddOn\Http\Resources\AddOnResource;
use Modules\AddOn\Services\AddOnService;
use Modules\AddOn\Models\AddOn;
use Illuminate\Http\JsonResponse;

class AddOnController extends Controller
{
    use ApiResponse, AuthorizesRequests;

    public function __construct(protected AddOnService $addOnService)
    {
    }

    /**
     * Display a listing of the add-ons.
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', AddOn::class);
        $addOns = $this->addOnService->getAllAddOns();

        return $this->successResponse([
            "add_ons" => AddOnResource::collection($addOns),
        ], __("message.success"));
    }

    /**
     * Store a newly created add-on.
     */
    public function store(CreateAddOnRequest $request): JsonResponse
    {
        $this->authorize('create', AddOn::class);
        $addOn = $this->addOnService->createAddOn($request->all());

        return $this->successResponse([
            "add_on" => new AddOnResource($addOn),
        ], __("message.success"));
    }

    /**
     * Display the specified add-on.
     */
    public function show(int $id): JsonResponse
    {
        $addOn = $this->addOnService->getAddOnById($id);
        $this->authorize('view', $addOn);

        return $this->successResponse([
            "add_on" => new AddOnResource($addOn),
        ], __("message.success"));
    }

    /**
     * Update the specified add-on.
     */
    public function update(UpdateAddOnRequest $request, int $id): JsonResponse
    {
        $addOn = $this->addOnService->getAddOnById($id);
        $this->authorize('update', $addOn);
        $addOn = $this->addOnService->updateAddOn($id, $request->all());

        return $this->successResponse([
            "add_on" => new AddOnResource($addOn),
        ], __("message.success"));
    }

    /**
     * Remove the specified add-on from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $addOn = $this->addOnService->getAddOnById($id);
        $this->authorize('delete', $addOn);
        $this->addOnService->deleteAddOn($id);

        return $this->successResponse(null, __("message.success"));
    }
}
