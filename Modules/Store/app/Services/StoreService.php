<?php

namespace Modules\Store\Services;

use Carbon\Carbon;
use App\Enums\OrderStatus;
use App\Helpers\CurrencyHelper;
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
use Illuminate\Support\Facades\Schema;

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

            // Get currency factor from the address data or country data for updates
            $currencyFactor = 100; // Default fallback

            // Try to get currency factor from the address being updated
            if (isset($data['address']['zone_id'])) {
                $zone = \Modules\Zone\Models\Zone::find($data['address']['zone_id']);
                if ($zone && $zone->city && $zone->city->governorate && $zone->city->governorate->country) {
                    $currencyFactor = $zone->city->governorate->country->currency_factor ?? 100;
                }
            } else {
                // For updates, try to get currency factor from existing store
                $store = $this->StoreRepository->find($id);
                if ($store && $store->address && $store->address->zone && $store->address->zone->city && $store->address->zone->city->governorate && $store->address->zone->city->governorate->country) {
                    $currencyFactor = $store->address->zone->city->governorate->country->currency_factor ?? 100;
                }
            }

            // Fallback: try to get from store data if provided
            if (!isset($data['address']['zone_id']) && isset($data['store']['currency_factor'])) {
                $currencyFactor = $data['store']['currency_factor'];
            }

            // Convert commission amount to minor units using the correct currency factor ONLY if it's a fixed amount (subscription)
            $currentStore = $this->StoreRepository->find($id);
            if (
                isset($data['store']['commission_amount']) &&
                ($data['store']['commission_type'] ?? $currentStore->commission_type->value) === \App\Enums\PlanTypeEnum::SUBSCRIPTION->value
            ) {
                $data['store']['commission_amount'] = CurrencyHelper::toMinorUnits($data['store']['commission_amount'], $currencyFactor);
            }

            $store = $this->StoreRepository->update($id, $data['store']);
            $store->storeSetting()->update([
                'orders_enabled' => $data['store']['orders_enabled'] ?? $store->storeSetting?->orders_enabled,
                'delivery_service_enabled' => $data['store']['delivery_service_enabled'] ?? $store->storeSetting?->delivery_service_enabled,
                'external_pickup_enabled' => $data['store']['external_pickup_enabled'] ?? $store->storeSetting?->external_pickup_enabled,
                'product_classification' => $data['store']['product_classification'] ?? $store->storeSetting?->product_classification,
                'self_delivery_enabled' => $data['store']['self_delivery_enabled'] ?? $store->storeSetting?->self_delivery_enabled,
                'free_delivery_enabled' => $data['store']['free_delivery_enabled'] ?? $store->storeSetting?->free_delivery_enabled,
                'minimum_order_amount' => $data['store']['minimum_order_amount'] ?? $store->storeSetting?->minimum_order_amount,
                'delivery_time_min' => $data['store']['delivery_time_min'] ?? $store->storeSetting?->delivery_time_min,
                'delivery_time_max' => $data['store']['delivery_time_max'] ?? $store->storeSetting?->delivery_time_max,
                'tax_rate' => $data['store']['tax_rate'] ?? $store->storeSetting?->tax_rate,
                'order_interval_time' => $data['store']['order_interval_time'] ?? $store->storeSetting?->order_interval_time,
                'service_fee_percentage' => $data['store']['service_fee_percentage'] ?? $store->storeSetting?->service_fee_percentage,
            ]);

            // Update address if provided
            if (isset($data['address'])) {
                $store->address()->update($data['address']);
            }

            // Sync zones to cover if provided
            if (isset($data['zones_to_cover']) && is_array($data['zones_to_cover'])) {
                $data['zones_to_cover'] = array_filter($data['zones_to_cover'], fn($value) => !blank($value));
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
        return DB::transaction(function () use ($id) {
            $store = $this->StoreRepository->find($id);

            if (!$store) {
                return false;
            }

            // Delete related entities in a specific order to handle foreign key constraints
            // 1. Delete many-to-many relationships first
            $store->coupons()->detach();
            $store->zoneToCover()->detach();

            // 2. Delete has-many relationships
            $store->products()->delete();
            $store->orders()->delete();
            $store->workingDays()->delete();
            $store->couriers()->delete();
            $store->reports()->delete();
            $store->posTerminals()->delete();
            $store->addOns()->delete();
            $store->categories()->delete();
            $store->branches()->delete();
            $store->CompaignParicipations()->delete();
            $store->couriers()->delete();
            $store->vendors()->delete();

            // 3. Delete morph-many relationships
            $store->favourites()->delete();
            $store->reviews()->delete();
            $store->banners()->delete();

            // 4. Delete has-one relationships
            $store->storeSetting()->delete();
            $store->address()->delete();
            $store->balance()->delete();

            // 5. Finally delete the store itself
            return $this->StoreRepository->delete($id);
        });
    }
    public function getHomeStores(array $filters = []): array
    {
        $relation = [
            'section.categories',
            'storeSetting',
            'address.zone.city.governorate.country',
            'address.zone.shippingPrices',
            'section',
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
            'total_orders' => (int) $stats['total_orders'],
            'today_revenue' => (int) $stats['today_revenue'],
            'pending_orders' => (int) $stats['pending_orders'],
            'average_order' => (int) $stats['average_order'],
            'latest_orders' => OrderResource::collection($latestOrders),
            'top_products' => $products,
            'currency_code' => $store->getCurrencyCode(),
            'currency_factor' => $store->getCurrencyFactor(),
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

            // Get currency factor from the address data or country data
            $currencyFactor = 100; // Default fallback

            // Try to get currency factor from the address being created
            if (isset($data['address']['zone_id'])) {
                $zone = \Modules\Zone\Models\Zone::find($data['address']['zone_id']);
                if ($zone && $zone->city && $zone->city->governorate && $zone->city->governorate->country) {
                    $currencyFactor = $zone->city->governorate->country->currency_factor ?? 100;
                }
            }

            // Fallback: try to get from store data if provided
            if (!isset($data['address']['zone_id']) && isset($data['store']['currency_factor'])) {
                $currencyFactor = $data['store']['currency_factor'];
            }

            // Convert commission amount to minor units using the correct currency factor ONLY if it's a fixed amount (subscription)
            if (
                isset($data['store']['commission_amount']) &&
                ($data['store']['commission_type'] ?? \App\Enums\PlanTypeEnum::COMMISSION->value) === \App\Enums\PlanTypeEnum::SUBSCRIPTION->value
            ) {
                $data['store']['commission_amount'] = CurrencyHelper::toMinorUnits($data['store']['commission_amount'], $currencyFactor);
            }

            $store =  $this->StoreRepository->create($data['store'] + ['zone_id' => $data['address']['zone_id']]);
            $store->address()->create($data['address']);
            $store->refresh();
            $store->load('address.zone.city.governorate.country'); // Load deep relations for currency factor

            if ($data['store']['type'] != 'delivery') {
                $store->storeSetting()->create([
                    'orders_enabled' => $data['store']['orders_enabled'] ?? true,
                    'delivery_service_enabled' => $data['store']['delivery_service_enabled'] ?? true,
                    'external_pickup_enabled' => $data['store']['external_pickup_enabled'] ?? false,
                    'product_classification' => $data['store']['product_classification'] ?? false,
                    'self_delivery_enabled' => $data['store']['self_delivery_enabled'] ?? true,
                    'free_delivery_enabled' => $data['store']['free_delivery_enabled'] ?? false,
                    'minimum_order_amount' => $data['store']['minimum_order_amount'] ?? 0,
                    'delivery_time_min' => $data['store']['delivery_time_min'] ?? 0,
                    'delivery_time_max' => $data['store']['delivery_time_max'] ?? 0,
                    'tax_rate' => $data['store']['tax_rate'] ?? 0,
                    'order_interval_time' => $data['store']['order_interval_time'] ?? 0,
                    'service_fee_percentage' => $data['store']['service_fee_percentage'] ?? 0,
                    'commission_amount' => $data['store']['commission_amount'] ?? 0,
                    'commission_type' => $data['store']['commission_type'] ?? 'commission',
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

    public function deliveryStoreStats($filter = [])
    {
        $currencyCode = config('settings.default_currency', 'USD');
        $currencyFactor = 100;
        $countryId = null;

        if (auth('sanctum')->check()) {
            $user = auth('sanctum')->user();
            if ($user->currentAccessToken() && $user->currentAccessToken()->country_id) {
                $countryId = $user->currentAccessToken()->country_id;
            }
            if (!$countryId) {
                $countryId = config('settings.default_country', 1);
            }
        } else {
            $countryId = config('settings.default_country', 1);
        }

        if ($countryId) {
            $country = \Modules\Country\Models\Country::find($countryId);
            if ($country) {
                $currencyCode = $country->currency_name ?? config('settings.default_currency', 'USD');
                $currencyFactor = $country->currency_factor ?? 100;
            }
        }

        $deliveryStores = Store::when($filter['store_id'] ?? null, function ($query) use ($filter) {
            $query->where('id', $filter['store_id']);
        })
            ->where('type', 'delivery')
            ->when($countryId, function ($query) use ($countryId) {
                $query->whereHas('address.zone.city.governorate', function ($q) use ($countryId) {
                    $q->where('country_id', $countryId);
                });
            })
            ->get();
        $totalDeliveryCompanies = $deliveryStores->count();

        $storeIds = $deliveryStores->pluck('id');

        $totalCouriers = Couier::whereIn('store_id', $storeIds)->count();

        // Only include orders whose assigned courier belongs to the selected delivery companies (courier.store_id in $storeIds)
        $totalOrders = Order::whereHas('courier', function ($q) use ($storeIds) {
            $q->whereIn('store_id', $storeIds);
        })->count();

        $successfulOrders = Order::whereHas('courier', function ($q) use ($storeIds) {
            $q->whereIn('store_id', $storeIds);
        })->where('status', OrderStatus::DELIVERED)->count();

        $totalRevenue = Order::whereHas('courier', function ($q) use ($storeIds) {
            $q->whereIn('store_id', $storeIds);
        })->where('payment_status', 'paid')->sum('total_amount');

        $successRate = $totalOrders > 0 ? round(($successfulOrders / $totalOrders) * 100, 2) : 0;

        return [
            'success_rate' => $successRate,
            'total_revenue' => $totalRevenue,
            'executed_orders' => $successfulOrders,
            'total_couriers' => $totalCouriers,
            'total_delivery_companies' => $totalDeliveryCompanies,
            'currency_factor' => $currencyFactor,
            'currency_code' => $currencyCode
        ];
    }
    public function deliveryStoreStatsInfo($filter = [])
    {
        $store = Store::when($filter['store_id'] ?? null, function ($query) use ($filter) {
            $query->where('id', $filter['store_id']);
        })
            ->where('type', 'delivery')
            ->first();

        if (!$store) {
            return null;
        }

        $totalCouriers = Couier::where('store_id', $store->id)->count();

        // Base query: only include orders whose assigned courier belongs to the selected delivery company
        $ordersQuery = Order::whereHas('courier', function ($q) use ($store) {
            $q->where('store_id', $store->id);
        });

        $totalOrders = (clone $ordersQuery)->count();

        $successfulOrders = (clone $ordersQuery)->where('status', OrderStatus::DELIVERED)->count();

        $ongoingOrders = (clone $ordersQuery)->whereIn('status', [
            OrderStatus::PENDING,
            OrderStatus::CONFIRMED,
            OrderStatus::PROCESSING,
            OrderStatus::READY_FOR_DELIVERY,
            OrderStatus::ON_THE_WAY,
        ])->count();

        $totalRevenue = (clone $ordersQuery)->where('payment_status', 'paid')->sum('total_amount');

        $successRate = $totalOrders > 0 ? round(($successfulOrders / $totalOrders) * 100, 2) : 0;
        $ongoingPercentage = $totalOrders > 0 ? round(($ongoingOrders / $totalOrders) * 100, 2) : 0;

        $currencyCode = $store->store_setting?->currency_code ?? $store->address?->zone?->city?->governorate?->country?->currency_name ?? 'EGP';
        $currencySymbol = $store->store_setting?->currency_symbol ?? $store->address?->zone?->city?->governorate?->country?->currency_symbol ?? $currencyCode;
        $totalRevenueFormatted = number_format($totalRevenue) . ' ' . $currencyCode;

        return [
            // Drivers count (السائقين)
            'total_couriers' => $totalCouriers,

            // Orders (الطلبات)
            'total_orders' => $totalOrders,

            // On-time / executed deliveries (التسليم في الوقت)
            'executed_orders' => $successfulOrders,

            // Total revenue (إجمالي الأرباح)
            'total_revenue' => $totalRevenue,
            'total_revenue_formatted' => $totalRevenueFormatted,
            'currency_code' => $currencyCode,
            'currency_symbol' => $currencySymbol,

            // Ongoing orders (الطلبات الجارية)
            'ongoing_orders' => $ongoingOrders,
            'ongoing_percentage' => $ongoingPercentage,
            'ongoing_percentage_formatted' => $ongoingPercentage . '%',

            // Success rate
            'success_rate' => $successRate,
        ];
    }
    public function deliveryStoreDailyPerformance($filter = [])
    {
        $store = Store::when($filter['store_id'] ?? null, function ($query) use ($filter) {
            $query->where('id', $filter['store_id']);
        })->where('type', 'delivery')->first();

        if (!$store) {
            return null;
        }

        // Last 7 days (6 days ago -> today)
        $days = collect(range(6, 0))->map(fn($i) => Carbon::today()->subDays($i));

        // Arabic weekday mapping where Carbon::dayOfWeek() returns 0=Sunday .. 6=Saturday
        $weekdayMap = [
            6 => 'السبت',
            0 => 'الأحد',
            1 => 'الاثنين',
            2 => 'الثلاثاء',
            3 => 'الأربعاء',
            4 => 'الخميس',
            5 => 'الجمعة',
        ];

        $labels = [];
        $orders = [];
        $delivered = [];
        $revenue = [];

        foreach ($days as $day) {
            $date = $day->toDateString();
            $labels[] = $weekdayMap[$day->dayOfWeek] ?? $day->format('D');

            $countOrders = Order::whereHas('courier', function ($q) use ($store) {
                $q->where('store_id', $store->id);
            })->whereDate('created_at', $date)->count();

            $countDelivered = Order::whereHas('courier', function ($q) use ($store) {
                $q->where('store_id', $store->id);
            })->whereDate('created_at', $date)->where('status', OrderStatus::DELIVERED)->count();

            $dayRevenue = Order::whereHas('courier', function ($q) use ($store) {
                $q->where('store_id', $store->id);
            })->whereDate('created_at', $date)->where('payment_status', 'paid')->sum('total_amount');

            $orders[] = $countOrders;
            $delivered[] = $countDelivered;
            $revenue[] = (float) $dayRevenue;
        }

        $maxVal = max(array_merge($orders ?: [0], $delivered ?: [0]));
        $step = $maxVal > 0 ? (int) ceil($maxVal / 4) : 1;
        $ticks = [$step * 4, $step * 3, $step * 2, $step * 1, 0];

        return [
            'labels' => $labels,
            'orders' => $orders,
            'delivered' => $delivered,
            'revenue' => $revenue,
            'ticks' => $ticks,
            'max' => $step * 4,
        ];
    }

    public function deliveryStoreQuickStats($filter = [])
    {
        $store = Store::when($filter['store_id'] ?? null, function ($query) use ($filter) {
            $query->where('id', $filter['store_id']);
        })->where('type', 'delivery')->first();

        if (!$store) {
            return null;
        }

        $ordersQuery = Order::whereHas('courier', function ($q) use ($store) {
            $q->where('store_id', $store->id);
        });

        $totalOrders = (clone $ordersQuery)->count();
        $successfulOrders = (clone $ordersQuery)->where('status', OrderStatus::DELIVERED)->count();
        $successRate = $totalOrders > 0 ? round(($successfulOrders / $totalOrders) * 100, 2) : 0;

        $start = Carbon::today()->subDays(6)->startOfDay();
        $end = Carbon::today()->endOfDay();

        $ordersLast7 = (clone $ordersQuery)->whereBetween('created_at', [$start, $end])->count();
        $revenueLast7 = (clone $ordersQuery)->whereBetween('created_at', [$start, $end])->where('payment_status', 'paid')->sum('total_amount');

        $avgOrdersPerDay = $ordersLast7 / 7;
        $avgRevenuePerDay = $revenueLast7 / 7;

        $avgRating = \App\Models\ReviewRating::whereHas('review', function ($q) use ($store) {
            $q->whereHas('order', function ($q2) use ($store) {
                $q2->where('store_id', $store->id);
            });
        })->avg('rating');

        $currencyCode = $store->store_setting?->currency_code ?? $store->address?->zone?->city?->governorate?->country?->currency_name ?? 'EGP';

        return [
            'success_rate' => $successRate,
            'success_rate_formatted' => $successRate . '%',
            'avg_orders_per_day' => round($avgOrdersPerDay, 1),
            'avg_orders_per_day_formatted' => number_format(round($avgOrdersPerDay, 1), 1),
            'avg_revenue_per_day' => round($avgRevenuePerDay, 2),
            'avg_revenue_per_day_formatted' => number_format((int) round($avgRevenuePerDay)) . ' ' . $currencyCode,
            'average_rating' => $avgRating ? round($avgRating, 1) : 0,
            'average_rating_formatted' => $avgRating ? number_format(round($avgRating, 1), 1) : '0.0',
        ];
    }

    public function deliveryStoreAchievements($filter = [])
    {
        $store = Store::when($filter['store_id'] ?? null, function ($query) use ($filter) {
            $query->where('id', $filter['store_id']);
        })->where('type', 'delivery')->first();

        if (!$store) {
            return null;
        }

        $ordersQuery = Order::whereHas('courier', function ($q) use ($store) {
            $q->where('store_id', $store->id);
        });

        // Current 30-day window
        $today = Carbon::today();
        $startCurrent = $today->copy()->subDays(29)->startOfDay();
        $endCurrent = $today->endOfDay();

        // Previous 30-day window
        $startPrev = $startCurrent->copy()->subDays(30)->startOfDay();
        $endPrev = $startCurrent->copy()->subDays(1)->endOfDay();

        $currentCount = (clone $ordersQuery)->whereBetween('created_at', [$startCurrent, $endCurrent])->count();
        $prevCount = (clone $ordersQuery)->whereBetween('created_at', [$startPrev, $endPrev])->count();

        if ($prevCount > 0) {
            $growth = round((($currentCount - $prevCount) / $prevCount) * 100, 1);
        } else {
            $growth = $currentCount > 0 ? 100 : 0;
        }

        $badge = $currentCount >= 100 ? 'شركة موثوقة' : null;

        return [
            'monthly_orders' => $currentCount,
            'monthly_orders_formatted' => $currentCount >= 100 ? '100+' : (string) $currentCount,
            'growth_percentage' => $growth,
            'growth_label' => 'الشهر الماضي',
            'badge' => $badge,
        ];
    }

    public function deliveryStoreMonthlyReport($filter = [])
    {
        $store = Store::when($filter['store_id'] ?? null, function ($query) use ($filter) {
            $query->where('id', $filter['store_id']);
        })->where('type', 'delivery')->first();

        if (!$store) {
            return null;
        }

        $ordersQuery = Order::whereHas('courier', function ($q) use ($store) {
            $q->where('store_id', $store->id);
        });

        $start = Carbon::now()->startOfMonth()->startOfDay();
        $end = Carbon::now()->endOfMonth()->endOfDay();

        $monthQuery = (clone $ordersQuery)->whereBetween('created_at', [$start, $end]);

        $monthlyDeliveries = $monthQuery->count();
        $monthlyDelivered = (clone $monthQuery)->where('status', OrderStatus::DELIVERED)->count();
        $onTimePercentage = $monthlyDeliveries > 0 ? round(($monthlyDelivered / $monthlyDeliveries) * 100, 1) : 0;

        $monthlyRevenue = (clone $monthQuery)->where('payment_status', 'paid')->sum('total_amount');
        $cancelledOrders = (clone $monthQuery)->where('status', OrderStatus::CANCELLED)->count();

        $currencyCode = $store->store_setting?->currency_code ?? $store->address?->zone?->city?->governorate?->country?->currency_name ?? 'EGP';

        return [
            'monthly_deliveries' => $monthlyDeliveries,
            'monthly_deliveries_formatted' => $monthlyDeliveries >= 100 ? '100+' : (string) $monthlyDeliveries,
            'on_time_percentage' => $onTimePercentage,
            'on_time_percentage_formatted' => $onTimePercentage . '%',
            'monthly_revenue' => $monthlyRevenue,
            'monthly_revenue_formatted' => number_format((int) $monthlyRevenue) . ' ' . $currencyCode,
            'cancelled_orders' => $cancelledOrders,
        ];
    }

    public function getBranches(int $storeId)
    {
        return $this->StoreRepository->getBranches($storeId);
    }
    public function getCommissionSettings(array $filters = []): array
    {
        // Get stores with custom commission settings
        $storesWithCustomCommissionCount = $this->StoreRepository->getStoresWithCustomCommissionCount();

        // Calculate commission statistics
        $totalPendingCommission = $this->StoreRepository->getTotalPendingCommission();
        $totalEarnedCommission = $this->StoreRepository->getTotalEarnedCommission();

        // Get stores with commission data
        $commissionStores = $this->StoreRepository->getCommissionStores($filters);

        return [
            'stores_with_custom_commission' => $storesWithCustomCommissionCount,
            'total_pending_commission' => (int) $totalPendingCommission,
            'total_earned_commission' => $totalEarnedCommission,
            'commission_stores' => \Modules\Store\Http\Resources\CommissionStoreResource::collection($commissionStores->getCollection()),
            'pagination' => new \App\Http\Resources\PaginationResource($commissionStores)
        ];
    }
}
