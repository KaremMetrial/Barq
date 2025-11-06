<?php

namespace Modules\Store\Services;

use Carbon\Carbon;
use Modules\Role\Models\Role;
use App\Traits\FileUploadTrait;
use Modules\Store\Models\Store;
use Illuminate\Support\Facades\DB;
use Modules\Product\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Collection;
use Modules\Order\Http\Resources\OrderResource;
use Modules\Store\Repositories\StoreRepository;

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
            $data['logo'] = $this->upload(
                request(),
                'logo',
                'uploads/logos',
                'public'
            );
            $data['cover_image'] = $this->upload(
                request(),
                'cover_image',
                'uploads/cover_images',
                'public'
            );
            $data = array_filter($data, fn($value) => !blank($value));
            return $this->StoreRepository->create($data);
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
            $data['logo'] = $this->upload(
                request(),
                'logo',
                'uploads/logos',
                'public'
            );
            $data['cover_image'] = $this->upload(
                request(),
                'cover_image',
                'uploads/cover_images',
                'public'
            );
            $data = array_filter($data, fn($value) => !blank($value));
            return $this->StoreRepository->update($id, $data);
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
    public function stats()
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
            ->with('store', 'user', 'courier', 'items.product', 'items.productOptionValue', 'items.addOns')
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
            ->with('translations')
            ->get()
            ->map(function ($product) use ($topProducts) {
                $sold = $topProducts->firstWhere('product_id', $product->id)->total_sold ?? 0;
                return [
                    'id' => $product->id,
                    'name' => $product->name ?? 'N/A',
                    'category' => $product->category->name ?? 'N/A',
                    'total_sold' => (int) $sold,
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
                'section' => function ($query) {
                    $query->withTranslation();
                },
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
                'logo',
                'uploads/logos',
                'public'
            );
            $data['store']['cover_image'] = $this->upload(
                request(),
                'cover_image',
                'uploads/cover_images',
                'public'
            );
            $data = array_filter($data, fn($value) => !blank($value));
            $store =  $this->StoreRepository->create($data['store']);
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
                'delivery_type_unit' => $data['store']['delivery_type_unit'] ?? 0,
                'tax_rate' => $data['store']['tax_rate'] ?? 0,
                'order_interval_time' => $data['store']['order_interval_time'] ?? 0,
                'service_fee_percentage' => $data['store']['service_fee_percentage'] ?? 0,
            ]);
            $data['vendor']['role_id'] = Role::where('name', 'store_owner')->first()->id;
            $store->vendors()->create($data['vendor']);

            return $store;
        });
    }
}
