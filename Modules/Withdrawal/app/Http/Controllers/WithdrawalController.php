<?php

namespace Modules\Withdrawal\Http\Controllers;

use App\Http\Resources\PaginationResource;
use App\Traits\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Modules\Withdrawal\Services\WithdrawalService;
use Modules\Withdrawal\Http\Resources\WithdrawalResource;
use Modules\Withdrawal\Http\Requests\CreateWithdrawalRequest;
use Modules\Withdrawal\Http\Requests\UpdateWithdrawalRequest;
use Modules\Withdrawal\Models\Withdrawal;

class WithdrawalController extends Controller
{
    use ApiResponse, AuthorizesRequests;

    public function __construct(protected WithdrawalService $WithdrawalService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Withdrawal::class);
        $Withdrawals = $this->WithdrawalService->getAllWithdrawals();
        return $this->successResponse([
            "Withdrawals" => WithdrawalResource::collection($Withdrawals->load('withdrawable')),
            "pagination" => new PaginationResource($Withdrawals)
        ], __("message.success"));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateWithdrawalRequest $request): JsonResponse
    {
        $this->authorize('create', Withdrawal::class);
        $Withdrawal = $this->WithdrawalService->createWithdrawal($request->all());
        return $this->successResponse([
            "Withdrawal" => new WithdrawalResource($Withdrawal)
        ], __("message.success"));
    }

    /**
     * Show the specified resource.
     */
    public function show(int $id): JsonResponse
    {
        $Withdrawal = $this->WithdrawalService->getWithdrawalById($id);
        $this->authorize('view', $Withdrawal);
        return $this->successResponse([
            "Withdrawal" => new WithdrawalResource($Withdrawal->load('withdrawable'))
        ], __("message.success"));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateWithdrawalRequest $request, int $id): JsonResponse
    {
        $Withdrawal = $this->WithdrawalService->getWithdrawalById($id);
        $this->authorize('update', $Withdrawal);
        $Withdrawal = $this->WithdrawalService->updateWithdrawal($id, $request->all());
        return $this->successResponse([
            "Withdrawal" => new WithdrawalResource($Withdrawal)
        ], __("message.success"));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $Withdrawal = $this->WithdrawalService->getWithdrawalById($id);
        $this->authorize('delete', $Withdrawal);
        $deleted = $this->WithdrawalService->deleteWithdrawal($id);
        return $this->successResponse(null, __("message.success"));
    }
}
