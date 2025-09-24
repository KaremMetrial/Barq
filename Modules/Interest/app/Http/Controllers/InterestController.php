<?php

namespace Modules\Interest\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Modules\Interest\Http\Requests\CreateInterestRequest;
use Modules\Interest\Http\Requests\UpdateInterestRequest;
use Modules\Interest\Http\Resources\InterestResource;
use Modules\Interest\Services\InterestService;
use Illuminate\Http\JsonResponse;

class InterestController extends Controller
{
    use ApiResponse;

    public function __construct(protected InterestService $interestService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $interests = $this->interestService->getAllInterests();
        return $this->successResponse([
            "interests" => InterestResource::collection($interests),
        ], __("message.success"));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateInterestRequest $request): JsonResponse
    {
        $interest = $this->interestService->createInterest($request->all());
        return $this->successResponse([
            "interest" => new InterestResource($interest),
        ], __("message.success"));
    }

    /**
     * Show the specified resource.
     */
    public function show(int $id): JsonResponse
    {
        $interest = $this->interestService->getInterestById($id);
        return $this->successResponse([
            "interest" => new InterestResource($interest),
        ], __("message.success"));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateInterestRequest $request, int $id): JsonResponse
    {
        $interest = $this->interestService->updateInterest($id, $request->all());
        return $this->successResponse([
            "interest" => new InterestResource($interest),
        ], __("message.success"));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->interestService->deleteInterest($id);
        return $this->successResponse(null, __("message.success"));
    }
}
