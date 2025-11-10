<?php

namespace Modules\ShippingPrice\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\ShippingPrice\Services\ShippingPriceService;
use Modules\ShippingPrice\Http\Resources\ShippingPriceResource;
use Modules\ShippingPrice\Http\Requests\CreateShippingPriceRequest;

class ShippingPriceController extends Controller
{
    use ApiResponse;
        public function __construct(protected ShippingPriceService $ShippingPriceService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $ShippingPrices = $this->ShippingPriceService->getAllShippingPrices();
        return $this->successResponse([
            "ShippingPrices" => ShippingPriceResource::collection($ShippingPrices)
        ], __('message.success'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateShippingPriceRequest $request)
    {
        $ShippingPrice = $this->ShippingPriceService->createShippingPrice($request->validated());

        return $this->successResponse([
            'ShippingPrice' => new ShippingPriceResource($ShippingPrice)
        ], __('message.success'));
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        $ShippingPrice = $this->ShippingPriceService->getShippingPriceById($id);
        return $this->successResponse([
            'ShippingPrice' => new ShippingPriceResource($ShippingPrice)
        ], __('message.success'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $ShippingPrice = $this->ShippingPriceService->updateShippingPrice($id, $request->validated());
        return $this->successResponse([
            'ShippingPrice' => new ShippingPriceResource($ShippingPrice)
        ], __('message.success'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $isDeleted = $this->ShippingPriceService->deleteShippingPrice($id);
        return $this->successResponse(null, __('message.success'));
    }
}
