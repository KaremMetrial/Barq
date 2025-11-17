<?php

namespace Modules\ShippingPrice\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\ShippingPrice\Services\ShippingPriceService;
use Modules\ShippingPrice\Http\Resources\ShippingPriceResource;
use Modules\ShippingPrice\Http\Resources\ShippingPriceCollectionResource;
use App\Http\Resources\PaginationResource;
use Modules\ShippingPrice\Http\Requests\CreateShippingPriceRequest;
use Modules\ShippingPrice\Http\Requests\UpdateMultipleShippingPriceRequest;

class ShippingPriceController extends Controller
{
    use ApiResponse;
        public function __construct(protected ShippingPriceService $ShippingPriceService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $zones = \Modules\Zone\Models\Zone::with('shippingPrices.vehicle')->filter($request->query())->paginate($request->get('per_page', 15));

        $result = $zones->getCollection()->map(function ($zone) {
            return new ShippingPriceCollectionResource($zone->shippingPrices);
        });

        return $this->successResponse([
            "ShippingPrices" => $result,
            "pagination" => new PaginationResource($zones)
        ], __('message.success'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateShippingPriceRequest $request)
    {
        $validated = $request->validated();
        $zoneId = $validated['zone_id'];
        $vehicles = $validated['vehicles'];

        $zone = \Modules\Zone\Models\Zone::findOrFail($zoneId);
        $shippingPricesData = [];

        foreach ($vehicles as $vehicleData) {
            $vehicle = \Modules\Vehicle\Models\Vehicle::findOrFail($vehicleData['vehicle_id']);
            $shippingPricesData[] = [
                'name' => $zone->name,
                'base_price' => $vehicleData['base_price'],
                'max_price' => $vehicleData['max_price'],
                'per_km_price' => $vehicleData['per_km_price'],
                'max_cod_price' => $vehicleData['max_cod_price'],
                'enable_cod' => $vehicleData['enable_cod'],
                'is_active' => $vehicleData['is_active'] ?? true,
                'zone_id' => $zoneId,
                'vehicle_id' => $vehicleData['vehicle_id'],
            ];
        }

        $shippingPrices = $this->ShippingPriceService->createMultipleShippingPrices($shippingPricesData);

        return $this->successResponse([
            'ShippingPrices' => ShippingPriceResource::collection($shippingPrices)
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
        // Check if it's a multiple update request
        if ($request->has('zone_id') && $request->has('vehicles')) {
            $updateRequest = new UpdateMultipleShippingPriceRequest();
            $updateRequest->merge($request->all());
            $validated = $updateRequest->validateResolved();

            $zoneId = $validated['zone_id'];
            $vehicles = $validated['vehicles'];

            $shippingPrices = $this->ShippingPriceService->updateMultipleShippingPrices($zoneId, $vehicles);

            return $this->successResponse([
                'ShippingPrices' => ShippingPriceResource::collection($shippingPrices)
            ], __('message.success'));
        }

        // Single update
        $ShippingPrice = $this->ShippingPriceService->updateShippingPrice($id, $request->validated());
        return $this->successResponse([
            'ShippingPrice' => new ShippingPriceResource($ShippingPrice)
        ], __('message.success'));
    }



    /**
     * Get shipping statistics
     */
    public function statistics()
    {
        $totalZonesWithShipping = \Modules\Zone\Models\Zone::whereHas('shippingPrices')->count();

        $totalActiveOrders = \Modules\Order\Models\Order::whereIn('status', [
            \App\Enums\OrderStatus::PENDING,
            \App\Enums\OrderStatus::CONFIRMED,
            \App\Enums\OrderStatus::PROCESSING,
            \App\Enums\OrderStatus::READY_FOR_DELIVERY,
            \App\Enums\OrderStatus::ON_THE_WAY
        ])->count();

        $averageDeliveryPrice = \Modules\Order\Models\Order::where('status', \App\Enums\OrderStatus::DELIVERED)
            ->where('delivery_fee', '>', 0)
            ->avg('delivery_fee');

        $totalCouriers = \Modules\Couier\Models\Couier::where('status', \App\Enums\UserStatusEnum::ACTIVE)->count();

        return $this->successResponse([
            'total_zones_with_shipping' => $totalZonesWithShipping,
            'total_active_orders' => $totalActiveOrders,
            'average_delivery_price' => round($averageDeliveryPrice ?? 0, 2),
            'total_couriers' => $totalCouriers,
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
