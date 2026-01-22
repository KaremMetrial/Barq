<?php

namespace Modules\ShippingPrice\Http\Controllers;

use App\Helpers\CurrencyHelper;
use App\Traits\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\ShippingPrice\Services\ShippingPriceService;
use Modules\ShippingPrice\Http\Resources\ShippingPriceResource;
use Modules\ShippingPrice\Http\Resources\ShippingPriceCollectionResource;
use App\Http\Resources\PaginationResource;
use Modules\ShippingPrice\Http\Requests\CreateShippingPriceRequest;
use Modules\ShippingPrice\Http\Requests\UpdateMultipleShippingPriceRequest;
use Modules\ShippingPrice\Models\ShippingPrice;

class ShippingPriceController extends Controller
{
    use ApiResponse, AuthorizesRequests;
        public function __construct(protected ShippingPriceService $ShippingPriceService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', ShippingPrice::class);
        $zones = \Modules\Zone\Models\Zone::with('shippingPrices.vehicle')->whereHas('shippingPrices')->filter($request->query())->paginate($request->get('per_page', 5));

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
        // $this->authorize('create', ShippingPrice::class);
        $validated = $request->validated();
        $zoneId = $validated['zone_id'];
        $vehicles = $validated['vehicles'];

        $zone = \Modules\Zone\Models\Zone::findOrFail($zoneId);
        $currencyFactor = $zone->getCurrencyFactor();
        $shippingPricesData = [];

        foreach ($vehicles as $vehicleData) {
            $vehicle = \Modules\Vehicle\Models\Vehicle::findOrFail($vehicleData['vehicle_id']);

            $shippingPricesData[] = [
                'name' => $zone->name,
                'base_price' => CurrencyHelper::toMinorUnits($vehicleData['base_price'], $currencyFactor),
                'max_price' => CurrencyHelper::toMinorUnits($vehicleData['max_price'], $currencyFactor),
                'per_km_price' => CurrencyHelper::toMinorUnits($vehicleData['per_km_price'], $currencyFactor),
                'max_cod_price' => CurrencyHelper::toMinorUnits($vehicleData['max_cod_price'], $currencyFactor),
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
        $this->authorize('view', $ShippingPrice);
        return $this->successResponse([
            'ShippingPrice' => new ShippingPriceResource($ShippingPrice)
        ], __('message.success'));
    }

    /**
     * Update the specified resource in storage.
     */
public function update(UpdateMultipleShippingPriceRequest $request, $id = null)
{
    // $this->authorize('update', ShippingPrice::class);
    $validated = $request->validated();
    $zoneId = $validated['zone_id'];
    $vehicles = $validated['vehicles'];

    $zone = \Modules\Zone\Models\Zone::findOrFail($zoneId);
    $currencyFactor = $zone->getCurrencyFactor();
    $shippingPricesData = [];

    foreach ($vehicles as $vehicleData) {
        $vehicle = \Modules\Vehicle\Models\Vehicle::findOrFail($vehicleData['vehicle_id']);

        // Prepare data with currency conversion
        $shippingPricesData[] = [
            'name' => $zone->name . ' - ' . $vehicle->name,
            'base_price' => CurrencyHelper::toMinorUnits($vehicleData['base_price'], $currencyFactor),
            'max_price' => CurrencyHelper::toMinorUnits($vehicleData['max_price'], $currencyFactor),
            'per_km_price' => CurrencyHelper::toMinorUnits($vehicleData['per_km_price'], $currencyFactor),
            'max_cod_price' => CurrencyHelper::toMinorUnits($vehicleData['max_cod_price'], $currencyFactor),
            'enable_cod' => $vehicleData['enable_cod'],
            'is_active' => $vehicleData['is_active'] ?? true,
            'zone_id' => $zoneId,
            'vehicle_id' => $vehicleData['vehicle_id'],
        ];
    }

    $updatedShippingPrices = [];

    foreach ($shippingPricesData as $data) {
        $vehicleId = $data['vehicle_id'];

        // Check if shipping price exists for this zone and vehicle
        $shippingPrice = $this->ShippingPriceService
            ->getShippingPriceByZoneAndVehicle($zoneId, $vehicleId);

        if ($shippingPrice) {
            // Update existing
            $updateData = $data;
            unset($updateData['vehicle_id']); // Remove vehicle_id from update
            $updatedShippingPrices[] = $this->ShippingPriceService
                ->updateShippingPrice($shippingPrice->id, $updateData);
        } else {
            // Create new
            $newData = $data;
            unset($newData['vehicle_id']); // Remove vehicle_id for creation
            $updatedShippingPrices[] = $this->ShippingPriceService
                ->createShippingPrice($newData);
        }
    }

    return $this->successResponse([
        'ShippingPrices' => ShippingPriceResource::collection($updatedShippingPrices)
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

        $totalCouriers = \Modules\Couier\Models\Couier::count();

        $totalDeliveryCompanies = \Modules\Store\Models\Store::where('type', 'delivery')->count();
        return $this->successResponse([
            'total_zones_with_shipping' => $totalZonesWithShipping,
            'total_active_orders' => $totalActiveOrders,
            'average_delivery_price' => round($averageDeliveryPrice ?? 0, 2),
            'total_couriers' => $totalCouriers,
            'total_delivery_companies' => $totalDeliveryCompanies,
        ], __('message.success'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $ShippingPrice = $this->ShippingPriceService->getShippingPriceById($id);
        $this->authorize('delete', $ShippingPrice);
        $isDeleted = $this->ShippingPriceService->deleteShippingPrice($id);
        return $this->successResponse(null, __('message.success'));
    }
}
