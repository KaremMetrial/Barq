<?php

namespace Modules\DeliveryInstruction\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Modules\DeliveryInstruction\Http\Requests\CreateDeliveryInstructionRequest;
use Modules\DeliveryInstruction\Http\Requests\UpdateDeliveryInstructionRequest;
use Modules\DeliveryInstruction\Http\Resources\DeliveryInstructionResource;
use Modules\DeliveryInstruction\Services\DeliveryInstructionService;

class DeliveryInstructionController extends Controller
{
    use ApiResponse;

    public function __construct(protected DeliveryInstructionService $deliveryInstructionService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $instructions = $this->deliveryInstructionService->getAllDeliveryInstructions();
        return $this->successResponse([
            "delivery_instructions" => DeliveryInstructionResource::collection($instructions),
        ], __("message.success"));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateDeliveryInstructionRequest $request): JsonResponse
    {
        $instruction = $this->deliveryInstructionService->createDeliveryInstruction($request->all());
        return $this->successResponse([
            "delivery_instruction" => new DeliveryInstructionResource($instruction),
        ], __("message.success"));
    }

    /**
     * Show the specified resource.
     */
    public function show(int $id): JsonResponse
    {
        $instruction = $this->deliveryInstructionService->getDeliveryInstructionById($id);
        return $this->successResponse([
            "delivery_instruction" => new DeliveryInstructionResource($instruction),
        ], __("message.success"));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDeliveryInstructionRequest $request, int $id): JsonResponse
    {
        $instruction = $this->deliveryInstructionService->updateDeliveryInstruction($id, $request->all());
        return $this->successResponse([
            "delivery_instruction" => new DeliveryInstructionResource($instruction),
        ], __("message.success"));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->deliveryInstructionService->deleteDeliveryInstruction($id);
        return $this->successResponse(null, __("message.success"));
    }
}
