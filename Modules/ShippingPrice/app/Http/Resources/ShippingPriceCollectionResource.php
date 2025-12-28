<?php

namespace Modules\ShippingPrice\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShippingPriceCollectionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if ($this->resource->isEmpty()) {
            return [
                'name' => null,
                'zone_id' => null,
                'is_active' => false,
                'base_prices' => [],
                'max_prices' => [],
                'per_km_prices' => [],
                'max_cod_prices' => [],
                'enable_cods' => [],
            ];
        }

        $zone = $this->first()->zone; // Assuming all in collection have same zone

        $basePrices = [];
        $maxPrices = [];
        $perKmPrices = [];
        $maxCodPrices = [];
        $enableCods = [];

        foreach ($this->resource as $shippingPrice) {
            $vehicleName = $shippingPrice->vehicle->name ?? 'Unknown Vehicle';
            $vehicleIcon = $shippingPrice->vehicle->icon ? asset('storage/'. $shippingPrice->vehicle->icon) : null;
            $basePrices[] = [
                'shipping_price_id' => $shippingPrice->id,
                'vehicle_id' => $shippingPrice->vehicle_id,
                'vehicle_name' => $vehicleName,
                'vehicle_icon' => $vehicleIcon,
                'base_price' => $shippingPrice->base_price,
            ];
            $maxPrices[] = [
                'shipping_price_id' => $shippingPrice->id,
                'vehicle_id' => $shippingPrice->vehicle_id,
                'vehicle_name' => $vehicleName,
                'vehicle_icon' => $vehicleIcon,
                'max_price' => $shippingPrice->max_price,
            ];
            $perKmPrices[] = [
                'shipping_price_id' => $shippingPrice->id,
                'vehicle_id' => $shippingPrice->vehicle_id,
                'vehicle_name' => $vehicleName,
                'vehicle_icon' => $vehicleIcon,
                'per_km_price' => $shippingPrice->per_km_price,
            ];
            $maxCodPrices[] = [
                'shipping_price_id' => $shippingPrice->id,
                'vehicle_id' => $shippingPrice->vehicle_id,
                'vehicle_name' => $vehicleName,
                'vehicle_icon' => $vehicleIcon,
                'max_cod_price' => $shippingPrice->max_cod_price,
            ];
            $enableCods[] = [
                'shipping_price_id' => $shippingPrice->id,
                'vehicle_id' => $shippingPrice->vehicle_id,
                'vehicle_name' => $vehicleName,
                'vehicle_icon' => $vehicleIcon,
                'enable_cod' => (bool) $shippingPrice->enable_cod,
            ];
        }

        $activeOrdersCount = $zone ? \Modules\Order\Models\Order::where('delivery_address_id', '!=', null)
            ->whereHas('deliveryAddress', function ($query) use ($zone) {
                $query->where('zone_id', $zone->id);
            })
            ->whereIn('status', [\App\Enums\OrderStatus::PENDING, \App\Enums\OrderStatus::CONFIRMED, \App\Enums\OrderStatus::PROCESSING, \App\Enums\OrderStatus::READY_FOR_DELIVERY, \App\Enums\OrderStatus::ON_THE_WAY])
            ->count() : 0;

        $totalCouriersCount = $zone ? \Modules\Couier\Models\Couier::whereHas('vehicle.vehicle', function ($query) use ($zone) {
                $query->whereHas('shippingPrices', function ($subQuery) use ($zone) {
                    $subQuery->where('zone_id', $zone->id);
                });
            })
            ->where('status', \App\Enums\UserStatusEnum::ACTIVE)
            ->count() : 0;

        return [
            'name' => $zone->name ?? null,
            'zone_id' => $zone->id ?? null,
            'is_active' => (bool) $this->resource->isNotEmpty(),
            'active_orders_count' => $activeOrdersCount,
            'total_couriers_count' => $totalCouriersCount,
            'base_prices' => $basePrices,
            'max_prices' => $maxPrices,
            'per_km_prices' => $perKmPrices,
            'max_cod_prices' => $maxCodPrices,
            'enable_cods' => $enableCods,
        ];
    }
}
