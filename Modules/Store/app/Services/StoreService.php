<?php

namespace Modules\Store\Services;

use Carbon\Carbon;
use App\Enums\OrderStatus;
use Modules\Role\Models\Role;
use App\Traits\FileUploadTrait;
use Modules\Store\Models\Store;
use Illuminate\Support\Facades\DB;
use Modules\Product\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Collection;
use Modules\Order\Http\Resources\OrderResource;
use Modules\Store\Repositories\StoreRepository;
use Modules\Order\Models\Order;
use Modules\Couier\Models\Couier;
use Modules\Address\Services\AddressService;
use Illuminate\Support\Facades\Log;

class StoreService
{
    use FileUploadTrait;
    public function __construct(
        protected StoreRepository $StoreRepository
    ) {}

    public function getAllStores($filters = [])
    {
        return $this->StoreRepository->paginate(
            $filters,
            5,
            [
                'section.categories' => function ($query) {
                    $query->whereNull('parent_id')->with('translations');
                },
                'storeSetting',
                'address',
                'translations',
                'section.translations',
                'currentUserFavourite',
                'offers',
            ]
        );
    }

    public function createStore(array $data)
    {
        return DB::transaction(function () use ($data) {
            $data['store']['logo'] = $this->upload(
                request(),
                'store.logo',
                'uploads/logos',
                'public'
            );
            $data['store']['cover_image'] = $this->upload(
                request(),
                'store.cover_image',
                'uploads/cover_images',
                'public'
            );
            $data = array_filter($data, fn($value) => !blank($value));
            return $this->StoreRepository->create($data['store']);
        });
    }

    public function getStoreById(int $id): ?Store
    {
        return $this->StoreRepository->find($id, [
            'section.categories' => function ($query) {
                $query->whereNull('parent_id')->with('translations');
            },
            'storeSetting',
            'section.categories.children.translations',
        ]);
    }

    public function updateStore(int $id, array $data): ?Store
    {
        return DB::transaction(function () use ($data, $id) {
            $data['store']['logo'] = $this->upload(
                request(),
                'store.logo',
                'uploads/logos',
                'public'
            );
            $data['store']['cover_image'] = $this->upload(
                request(),
                'store.cover_image',
                'uploads/cover_images',
                'public'
            );
            $data['store'] = array_filter($data['store'], fn($value) => !blank($value));
            $store = $this->StoreRepository->update($id, $data['store']);

            // Sync zones to cover if provided
            if (isset($data['zones_to_cover']) && is_array($data['zones_to_cover'])) {
                $store->zoneToCover()->sync($data['zones_to_cover']);
            }

            // Update working days if provided
            if (isset($data['working_days']) && is_array($data['working_days'])) {
                // Delete existing working days
                $store->workingDays()->delete();

                // Create new working days
                foreach ($data['working_days'] as $workingDay) {
                    $store->workingDays()->create($workingDay);
                }
            }

            return $store;
        });
    }

    public function deleteStore(int $id): bool
    {
        return $this->StoreRepository->delete($id);
    }
    public function getHomeStores(array $filters = []): array
    {
        $relation = [
            'section.categories.translations',
            'storeSetting',
            'address',
            'translations',
            'section.translations',
            'currentUserFavourite',
            'offers',
        ];
        return $this->StoreRepository->getHomeStores($relation, $filters);
    }
    public function vendorStats()
    {
        $store = auth('vendor')->user()->store;
        $today = Carbon::today()->toDateString();

        $stats = $store->orders()
            ->selectRaw('
                COUNT(*) as total_orders,
                SUM(CASE WHEN DATE(created_at) = ? AND payment_status = "paid" THEN total_amount ELSE 0 END) as today_revenue,
                SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_orders,
                AVG(total_amount) as average_order
            ', [$today])
            ->first();

        $latestOrders = $store->orders()
            ->latest()
            ->take(5)
            ->with('store', 'user', 'courier', 'items.product')
            ->get();

        $topProducts = \DB::table('order_items')
            ->select('product_id', \DB::raw('SUM(quantity) as total_sold'))
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.store_id', $store->id)
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->limit(5)
            ->get();

        $productIds = $topProducts->pluck('product_id')->toArray();
        $products = Product::whereIn('id', $productIds)
            ->with(['translations', 'category.translations', 'images'])
            ->get()
            ->map(function ($product) use ($topProducts) {
                $sold = $topProducts->firstWhere('product_id', $product->id)->total_sold ?? 0;
                return [
                    'id' => $product->id,
                    'name' => $product->name ?? 'N/A',
                    'category' => $product->category->name ?? 'N/A',
                    'total_sold' => (int) $sold,
                    "image" => $product->images->first()?->image_path ? asset('storage/' . $product->images->first()->image_path) : null,
                ];
            });

        return [
            'total_orders' => (string) $stats['total_orders'],
            'today_revenue' => (string) $stats['today_revenue'],
            'pending_orders' => (string) $stats['pending_orders'],
            'average_order' => (string) $stats['average_order'],
            'latest_orders' => OrderResource::collection($latestOrders),
            'top_products' => $products,
        ];
    }
    public function adminStats()
    {
        return $this->StoreRepository->stats();
    }
    public function getAdminAllStores($filters = [])
    {
        return $this->StoreRepository->paginate(
            $filters,
            5,
            [
                'section',
                'translations',
                'owner'
            ]
        );
    }
    public function createAdminStore(array $data)
    {
        return DB::transaction(function () use ($data) {
            $data['store']['logo'] = $this->upload(
                request(),
                'store.logo',
                'uploads/logos',
                'public'
            );
            $data['store']['cover_image'] = $this->upload(
                request(),
                'store.cover_image',
                'uploads/cover_images',
                'public'
            );
            $data = array_filter($data, fn($value) => !blank($value));
            $store =  $this->StoreRepository->create($data['store'] + ['zone_id' => $data['address']['zone_id']]);
            $store->address()->create($data['address']);
            if ($data['store']['type'] != 'delivery') {
                $store->storeSetting()->create([
                    'orders_enabled' => $data['store']['orders_enabled'] ?? false,
                    'delivery_service_enabled' => $data['storeSetting']['delivery_service_enabled'] ?? false,
                    'external_pickup_enabled' => $data['store']['external_pickup_enabled'] ?? false,
                    'product_classification' => $data['store']['product_classification'] ?? false,
                    'self_delivery_enabled' => $data['store']['self_delivery_enabled'] ?? false,
                    'free_delivery_enabled' => $data['store']['free_delivery_enabled'] ?? false,
                    'minimum_order_amount' => $data['store']['minimum_order_amount'] ?? 0,
                    'delivery_time_min' => $data['store']['delivery_time_min'] ?? 0,
                    'delivery_time_max' => $data['store']['delivery_time_max'] ?? 0,
                    'tax_rate' => $data['store']['tax_rate'] ?? 0,
                    'order_interval_time' => $data['store']['order_interval_time'] ?? 0,
                    'service_fee_percentage' => $data['store']['service_fee_percentage'] ?? 0,
                    'commission_amount' => $data['store']['commission_amount'] ?? 0,
                    'commission_type' => $data['store']['commission_type'] ?? 'percentage',
                ]);
                if (isset($data['vendor'])) {
                    $data['vendor']['role_id'] = \Spatie\Permission\Models\Role::where('name', 'store_owner')->first()->id;
                    $vendor = $store->vendors()->create($data['vendor']);
                    $vendor->assignRole('store_owner');
                }
                // Attach zones to cover if provided
                if (isset($data['zones_to_cover']) && is_array($data['zones_to_cover'])) {
                    $store->zoneToCover()->attach($data['zones_to_cover']);
                }

                // Create working days if provided
                if (isset($data['working_days']) && is_array($data['working_days'])) {
                    foreach ($data['working_days'] as $workingDay) {
                        $store->workingDays()->create($workingDay);
                    }
                }
            }
            return $store->refresh();
        });
    }
    public function deliveryStore()
    {
        return $this->StoreRepository->deliveryStore();
    }

    public function deliveryStoreStats()
    {
        $deliveryStores = Store::where('type', 'delivery')->get();
        $totalDeliveryCompanies = $deliveryStores->count();

        $storeIds = $deliveryStores->pluck('id');

        $totalCouriers = Couier::whereIn('store_id', $storeIds)->count();
        $totalOrders = Order::whereIn('store_id', $storeIds)->count();
        $successfulOrders = Order::whereIn('store_id', $storeIds)->where('status', OrderStatus::DELIVERED)->count();
        $totalRevenue = Order::whereIn('store_id', $storeIds)->where('payment_status', 'paid')->sum('total_amount');

        $successRate = $totalOrders > 0 ? round(($successfulOrders / $totalOrders) * 100, 2) : 0;

        return [
            'success_rate' => $successRate,
            'total_revenue' => $totalRevenue,
            'executed_orders' => $successfulOrders,
            'total_couriers' => $totalCouriers,
            'total_delivery_companies' => $totalDeliveryCompanies,
        ];
    }

    public function getBranches(int $storeId)
    {
        return $this->StoreRepository->getBranches($storeId);
    }
}
