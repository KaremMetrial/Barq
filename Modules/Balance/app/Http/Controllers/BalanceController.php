<?php

namespace Modules\Balance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Modules\Balance\Http\Requests\CreateBalanceRequest;
use Modules\Balance\Http\Requests\UpdateBalanceRequest;
use Modules\Balance\Http\Resources\BalanceResource;
use Modules\Balance\Services\BalanceService;

class BalanceController extends Controller
{
    use ApiResponse;

    public function __construct(protected BalanceService $balanceService)
    {
    }

    /**
     * Display a listing of balances.
     */
    public function index(): JsonResponse
    {
        $balances = $this->balanceService->getAllBalances();
        return $this->successResponse([
            "balances" => BalanceResource::collection($balances),
        ], __("message.success"));
    }

    /**
     * Store a newly created balance.
     */
    public function store(CreateBalanceRequest $request): JsonResponse
    {
        $balance = $this->balanceService->createBalance($request->all());
        return $this->successResponse([
            "balance" => new BalanceResource($balance),
        ], __("message.success"));
    }

    /**
     * Show a specific balance.
     */
    public function show(int $id): JsonResponse
    {
        $balance = $this->balanceService->getBalanceById($id);
        return $this->successResponse([
            "balance" => new BalanceResource($balance),
        ], __("message.success"));
    }

    /**
     * Update a balance.
     */
    public function update(UpdateBalanceRequest $request, int $id): JsonResponse
    {
        $balance = $this->balanceService->updateBalance($id, $request->all());
        return $this->successResponse([
            "balance" => new BalanceResource($balance),
        ], __("message.success"));
    }

    /**
     * Delete a balance.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->balanceService->deleteBalance($id);
        return $this->successResponse(null, __("message.success"));
    }
}
