<?php

namespace Modules\Store\Http\Controllers\Admin;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Modules\Store\Models\Store;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Modules\Store\Services\StoreService;
use App\Http\Resources\PaginationResource;
use Modules\Store\Http\Resources\Admin\StoreResource;
use Modules\Store\Http\Requests\CreateStoreRequest;
use Modules\Store\Http\Requests\UpdateStoreRequest;
use Modules\Store\Http\Resources\Admin\StoreCollectionResource;
use Modules\Store\Http\Resources\Admin\DeliveryCompanyResource;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class StoreController extends Controller implements HasMiddleware
{
    use ApiResponse;
    public static function middleware(): array
    {
        return [
            'auth:sanctum',
            new Middleware('ability:admin', only: ['store']),
        ];
    }

    public function __construct(protected StoreService $StoreService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'search',
            'status',
            'section_id',
            'category_id',
            'rating'
        ]);
        $Stores = $this->StoreService->getAdminAllStores($filters);
        return $this->successResponse([
            "Stores" => StoreCollectionResource::collection($Stores),
            "pagination" => new PaginationResource($Stores)
        ], __('message.success'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateStoreRequest $request): JsonResponse
    {
        $Store = $this->StoreService->createAdminStore($request->all());
        return $this->successResponse([
            'Store' => new StoreCollectionResource($Store->refresh())
        ], __('message.success'));
    }

    /**
     * Show the specified resource.
     */
    public function show(int $id): JsonResponse
    {
        $Store = $this->StoreService->getStoreById($id);
        return $this->successResponse([
            'Store' => new StoreResource($Store->load(['address', 'zoneToCover', 'workingDays', 'owner', 'storeSetting', 'parent']))
        ], __('message.success'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateStoreRequest $request, int $id): JsonResponse
    {
        $Store = $this->StoreService->updateStore($id, $request->all());
        return $this->successResponse([
            'Store' => new StoreResource($Store)
        ], __('message.success'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $isDeleted = $this->StoreService->deleteStore($id);
        return $this->successResponse(null, __('message.success'));
    }
    public function stats()
    {
        $statsCount = $this->StoreService->adminStats();
        return $this->successResponse([
            'vendor_count' => $statsCount['vendorCount'],
            'store_count' => $statsCount['storeCount'],
            'pos_count' => $statsCount['posCount'],
        ], __('message.success'));
    }
    public function deliveryStore()
    {
        $deliveryCompany = $this->StoreService->deliveryStore();
        return $this->successResponse([
            'delivery_company' => DeliveryCompanyResource::collection($deliveryCompany->getCollection()),
            'pagination' => new PaginationResource($deliveryCompany)
        ], __('message.success'));
    }
    public function deliveryStoreStats(Request $request)
    {
        $filter = $request->only(['store_id', 'from_date', 'to_date']);
        $stats = $this->StoreService->deliveryStoreStats($filter);
        return $this->successResponse($stats, __('message.success'));
    }

    public function branches(int $id): JsonResponse
    {
        $branches = $this->StoreService->getBranches($id);
        return $this->successResponse([
            'branches' => StoreCollectionResource::collection($branches),
            'pagination' => new PaginationResource($branches)
        ], __('message.success'));
    }
    public function deliveryStoreInfo(int $id): JsonResponse
    {
        $store = $this->StoreService->getStoreById($id);

        if (!$store) {
            return $this->errorResponse(__('message.not_found'), 404);
        }

        $stats = $this->StoreService->deliveryStoreStatsInfo(['store_id' => $id]);

        $data = [
            'store' => new StoreResource($store->load(['address', 'zoneToCover', 'workingDays', 'owner', 'storeSetting', 'parent'])),
            'delivery_stats' => $stats,
            'delivery_chart' => $this->StoreService->deliveryStoreDailyPerformance(['store_id' => $id]),
            'quick_stats' => $this->StoreService->deliveryStoreQuickStats(['store_id' => $id]),
            'achievements' => $this->StoreService->deliveryStoreAchievements(['store_id' => $id]),
            'performance_reports' => $this->StoreService->deliveryStoreMonthlyReport(['store_id' => $id]),
        ];

        return $this->successResponse($data, __('message.success'));
    }
    public function deliveryStoreZoneToCover(int $id): JsonResponse
    {
        $store = $this->StoreService->getStoreById($id);

        if (!$store) {
            return $this->errorResponse(__('message.not_found'), 404);
        }

        // Zones covered by couriers that belong to this store
        $zones = \Modules\Zone\Models\Zone::whereHas('couriers', function ($q) use ($id) {
            $q->where('store_id', $id);
        })->with(['city.governorate.country'])->get();
        $rows = $zones->map(function ($zone) use ($id) {
            $activeOrders = \Modules\Order\Models\Order::whereHas('deliveryAddress', function ($q) use ($zone) {
                $q->where('zone_id', $zone->id);
            })->whereIn('status', [
                \App\Enums\OrderStatus::PENDING,
                \App\Enums\OrderStatus::CONFIRMED,
                \App\Enums\OrderStatus::PROCESSING,
                \App\Enums\OrderStatus::READY_FOR_DELIVERY,
                \App\Enums\OrderStatus::ON_THE_WAY,
            ])->count();

            $couriersCount = $zone->couriers()->where('store_id', $id)->count();

            return [
                'zone_id' => $zone->id,
                'zone' => $zone->name ?? null,
                'city' => $zone->city?->name ?? null,
                'governorate' => $zone->city?->governorate?->name ?? null,
                'country' => $zone->city?->governorate?->country?->name ?? null,
                'active_orders' => $activeOrders,
                'active_orders_label' => $activeOrders . ' طلب' . ($activeOrders === 1 ? '' : 'ات'),
                'couriers_count' => $couriersCount,
                'couriers_label' => $couriersCount . ' سائق',
            ];
        })->values();

        return $this->successResponse([
            'zones_to_cover' => $rows,
        ], __('message.success'));
    }
    public function commissionSettings(Request $request): JsonResponse
    {
        $commissionData = $this->StoreService->getCommissionSettings($request->all());
        return $this->successResponse($commissionData, __('message.success'));
    }
}
