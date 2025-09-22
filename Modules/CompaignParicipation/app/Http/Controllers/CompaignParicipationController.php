<?php

namespace Modules\CompaignParicipation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Modules\CompaignParicipation\Models\CompaignParicipation;
use Modules\CompaignParicipation\Http\Requests\CreateCompaignParicipationRequest;
use Modules\CompaignParicipation\Http\Requests\UpdateCompaignParicipationRequest;
use Modules\CompaignParicipation\Http\Resources\CompaignParicipationResource;
use Modules\CompaignParicipation\Services\CompaignParicipationService;

class CompaignParicipationController extends Controller
{
    use ApiResponse;

    public function __construct(protected CompaignParicipationService $compaignParicipationService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $participations = $this->compaignParicipationService->getAllCompaignParicipations();
        return $this->successResponse([
            "participations" => CompaignParicipationResource::collection($participations)
        ], __('message.success'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateCompaignParicipationRequest $request): JsonResponse
    {
        $participation = $this->compaignParicipationService->createCompaignParicipation($request->all())->fresh();
        return $this->successResponse([
            'participation' => new CompaignParicipationResource($participation)
        ], __('message.success'));
    }

    /**
     * Show the specified resource.
     */
    public function show(int $id): JsonResponse
    {
        $participation = $this->compaignParicipationService->getCompaignParicipationById($id);
        return $this->successResponse([
            'participation' => new CompaignParicipationResource($participation)
        ], __('message.success'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCompaignParicipationRequest $request, int $id): JsonResponse
    {
        $participation = $this->compaignParicipationService->updateCompaignParicipation($id, $request->all());
        return $this->successResponse([
            'participation' => new CompaignParicipationResource($participation)
        ], __('message.success'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $isDeleted = $this->compaignParicipationService->deleteCompaignParicipation($id);
        return $this->successResponse(null, __('message.success'));
    }
}
