<?php

namespace Modules\Compaign\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Modules\Compaign\Http\Requests\CreateCompaignRequest;
use Modules\Compaign\Http\Requests\UpdateCompaignRequest;
use Modules\Compaign\Http\Resources\CompaignResource;
use Modules\Compaign\Services\CompaignService;
use Illuminate\Http\JsonResponse;
use Modules\Compaign\Models\Compaign;

class CompaignController extends Controller
{
    use ApiResponse;

    public function __construct(protected CompaignService $compaignService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $compaigns = $this->compaignService->getAllCompaigns();
        return $this->successResponse([
            "compaigns" => CompaignResource::collection($compaigns)
        ], __('message.success'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateCompaignRequest $request): JsonResponse
    {
        $compaign = $this->compaignService->createCompaign($request->all());
        return $this->successResponse([
            'compaign' => new CompaignResource($compaign)
        ], __('message.success'));
    }

    /**
     * Show the specified resource.
     */
    public function show(int $id): JsonResponse
    {
        $compaign = $this->compaignService->getCompaignById($id);
        return $this->successResponse([
            'compaign' => new CompaignResource($compaign)
        ], __('message.success'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCompaignRequest $request, int $id): JsonResponse
    {
        $compaign = $this->compaignService->updateCompaign($id, $request->all());
        return $this->successResponse([
            'compaign' => new CompaignResource($compaign)
        ], __('message.success'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $isDeleted = $this->compaignService->deleteCompaign($id);
        return $this->successResponse(null, __('message.success'));
    }

    /**
     * Get participants leaderboard.
     */
    public function participants(int $id): JsonResponse
    {
        $participants = \Modules\CompaignParicipation\Models\CompaignParicipation::where('compaign_id', $id)
            ->with(['store' => function ($q) {
                $q->select('id', 'logo');
                $q->withTranslation();
            }])
            ->orderByDesc('points')
            ->paginate(50);

        return $this->successResponse($participants, __('message.success'));
    }
}
