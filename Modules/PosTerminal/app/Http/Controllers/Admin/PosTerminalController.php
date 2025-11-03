<?php

namespace Modules\PosTerminal\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use GuzzleHttp\Psr7\Request;
use Illuminate\Http\JsonResponse;
use Modules\PosTerminal\Http\Requests\CreatePosTerminalRequest;
use Modules\PosTerminal\Http\Requests\UpdatePosTerminalRequest;
use Modules\PosTerminal\Http\Resources\PosTerminalResource;
use Modules\PosTerminal\Services\PosTerminalService;

class PosTerminalController extends Controller
{
    use ApiResponse;

    public function __construct(protected PosTerminalService $posTerminalService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $terminals = $this->posTerminalService->getAllPosTerminals($request->all());
        dd($terminals);
        return $this->successResponse([
            "terminals" => PosTerminalResource::collection($terminals)
        ], __('message.success'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreatePosTerminalRequest $request): JsonResponse
    {
        $terminal = $this->posTerminalService->createPosTerminal($request->all());
        return $this->successResponse([
            'terminal' => new PosTerminalResource($terminal)
        ], __('message.success'));
    }

    /**
     * Show the specified resource.
     */
    public function show(int $id): JsonResponse
    {
        $terminal = $this->posTerminalService->getPosTerminalById($id);
        return $this->successResponse([
            'terminal' => new PosTerminalResource($terminal)
        ], __('message.success'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePosTerminalRequest $request, int $id): JsonResponse
    {
        $terminal = $this->posTerminalService->updatePosTerminal($id, $request->all());
        return $this->successResponse([
            'terminal' => new PosTerminalResource($terminal)
        ], __('message.success'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $isDeleted = $this->posTerminalService->deletePosTerminal($id);
        return $this->successResponse(null, __('message.success'));
    }
}
