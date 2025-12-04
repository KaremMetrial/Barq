<?php

namespace Modules\LoyaltySetting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Modules\LoyaltySetting\Services\LoyaltySettingService;
use Modules\LoyaltySetting\Http\Requests\StoreLoyaltySettingRequest;
use Modules\LoyaltySetting\Http\Requests\UpdateLoyaltySettingRequest;
use Modules\LoyaltySetting\Http\Resources\LoyaltySettingResource;

class LoyaltySettingController extends Controller
{
    use ApiResponse;

    // Injecting LoyaltySettingService to manage business logic
    public function __construct(private LoyaltySettingService $LoyaltySettingService) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $LoyaltySettings = $this->LoyaltySettingService->getAllLoyaltySettings();
        return $this->successResponse([
            "LoyaltySettings" => LoyaltySettingResource::collection($LoyaltySettings->load('country')),
        ], __("message.success"));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreLoyaltySettingRequest $request)
    {
        $LoyaltySetting = $this->LoyaltySettingService->createLoyaltySetting($request->all());
        return $this->successResponse([
            "LoyaltySetting" => new LoyaltySettingResource($LoyaltySetting),
        ], __("message.success"));
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        $LoyaltySetting = $this->LoyaltySettingService->getLoyaltySettingById($id);
        return $this->successResponse([
            "LoyaltySetting"=> new LoyaltySettingResource($LoyaltySetting),
        ], __("message.success"));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateLoyaltySettingRequest $request, $id)
    {
        $LoyaltySetting = $this->LoyaltySettingService->updateLoyaltySetting($id, $request->all());
        return $this->successResponse([
            "LoyaltySetting"=> new LoyaltySettingResource($LoyaltySetting),
        ], __("message.success"));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $deleted = $this->LoyaltySettingService->deleteLoyaltySetting($id);
        return $this->successResponse(null, __("message.success"));
    }
}
