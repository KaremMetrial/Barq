<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use App\Enums\CouierAvaliableStatusEnum;
use App\Enums\OrderStatus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Modules\Order\Models\Order;
use Modules\Store\Models\Store;
use Modules\Couier\Models\Couier;
use Illuminate\Database\Eloquent\Builder;
use Modules\Section\Models\Section;
use App\Enums\SectionTypeEnum;
use App\Enums\StoreStatusEnum;

class DashboardController extends Controller
{
    use ApiResponse;

    private const CACHE_TTL = 3600; // 1 hour in seconds

    /**
     * Handle the dashboard request.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request)
    {
        $filters = $this->validateFilters($request->only(['store_id', 'period', 'zone_id']));

        try {
            $widget = [
                'order_widget' => $this->orderWidget($filters),
                'sales_widget' => $this->salesWidget($filters),
                'active_stores_widget' => $this->activeStoresWidget($filters),
                'active_couriers_widget' => $this->activeCouriersWidget($filters),
            ];

            $areaChart = $this->areaChart($filters);
            $pieChart = $this->pieChart($filters);
            $topStores = $this->topStores($filters);
            $latestOrders = $this->latestOrders($filters);
            return $this->successResponse([
                'widget' => $widget,
                'area_chart' => $areaChart,
                'pie_chart' => $pieChart,
                'top_stores' => $topStores,
                'latest_orders' => $latestOrders,
            ], __('message.success'));
        } catch (\Exception $e) {
            return $this->errorResponse(
                __('message.dashboard_error'),
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Get order-related widget data with caching and filtering.
     */
    protected function orderWidget(array $filters = []): array
    {
        $cacheKey = 'dashboard_order_widget_' . md5(serialize($filters));

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($filters) {
            $query = Order::query();
            $this->applyFilters($query, $filters);

            $totalOrders = (clone $query)->count();

            $period = $this->getPeriodDates($filters['period'] ?? 'month');
            $periodOrders = (clone $query)->whereBetween('created_at', [
                $period['current_start'],
                $period['current_end']
            ])->count();

            $previousPeriodOrders = (clone $query)->whereBetween('created_at', [
                $period['previous_start'],
                $period['previous_end']
            ])->count();

            [$periodRate, $periodTrend] = $this->calculateGrowthRate($periodOrders, $previousPeriodOrders);

            return [
                'totalOrders' => $totalOrders,
                'periodOrders' => $periodOrders,
                'periodRate' => $periodRate,
                'periodTrend' => $periodTrend,
            ];
        });
    }

    /**
     * Get sales widget data with caching and filtering.
     */
    protected function salesWidget(array $filters = []): array
    {
        $cacheKey = 'dashboard_sales_widget_' . md5(serialize($filters));

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($filters) {
            $query = Order::query();
            $this->applyFilters($query, $filters);

            $totalSales = round((clone $query)->sum('total_amount'), 2);

            $period = $this->getPeriodDates($filters['period'] ?? 'month');
            $periodSales = round((clone $query)->whereBetween('created_at', [
                $period['current_start'],
                $period['current_end']
            ])->sum('total_amount'), 2);

            $previousPeriodSales = round((clone $query)->whereBetween('created_at', [
                $period['previous_start'],
                $period['previous_end']
            ])->sum('total_amount'), 2);

            [$periodRate, $periodTrend] = $this->calculateGrowthRate($periodSales, $previousPeriodSales);

            return [
                'totalSales' => $totalSales,
                'periodSales' => $periodSales,
                'periodRate' => $periodRate,
                'periodTrend' => $periodTrend,
            ];
        });
    }

    /**
     * Get active stores widget data with caching and filtering.
     */
    protected function activeStoresWidget(array $filters = []): array
    {
        return $this->getWidgetData(
            cacheKey: 'dashboard_active_stores_widget',
            filters: $filters,
            model: Store::class,
            countCallback: fn($query) => $query->where('is_active', true)->count(),
            applyFiltersCallback: [$this, 'applyStoreFilters']
        );
    }

    /**
     * Get active couriers widget data with caching and filtering.
     */
    protected function activeCouriersWidget(array $filters = []): array
    {
        return $this->getWidgetData(
            cacheKey: 'dashboard_active_couriers_widget',
            filters: $filters,
            model: Couier::class,
            countCallback: fn($query) => $query->where('avaliable_status', CouierAvaliableStatusEnum::AVAILABLE)->count(),
            applyFiltersCallback: [$this, 'applyCourierFilters']
        );
    }

    /**
     * Generic widget data generator to reduce code duplication.
     * 
     * This is used for widgets where the total count and period count use the same calculation.
     * For widgets with different calculations (like sales), use separate methods.
     */
    private function getWidgetData(
        string $cacheKey,
        array $filters,
        string $model,
        callable $countCallback,
        callable $applyFiltersCallback
    ): array {
        $cacheKey = "{$cacheKey}_" . md5(serialize($filters));

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use (
            $model,
            $filters,
            $countCallback,
            $applyFiltersCallback
        ) {
            $query = $model::query();
            $applyFiltersCallback($query, $filters);

            $totalCount = $countCallback((clone $query));
            $period = $this->getPeriodDates($filters['period'] ?? 'month');

            $periodQuery = (clone $query);
            $periodCount = $countCallback($periodQuery->whereBetween('created_at', [
                $period['current_start'],
                $period['current_end']
            ]));

            $previousPeriodQuery = (clone $query);
            $previousPeriodCount = $countCallback($previousPeriodQuery->whereBetween('created_at', [
                $period['previous_start'],
                $period['previous_end']
            ]));

            [$periodRate, $periodTrend] = $this->calculateGrowthRate($periodCount, $previousPeriodCount);

            // Determine the appropriate keys based on model type
            $keyPrefix = $this->getWidgetKeyPrefix($model);

            return [
                "total{$keyPrefix}" => $totalCount,
                "period{$keyPrefix}" => $periodCount,
                'periodRate' => $periodRate,
                'periodTrend' => $periodTrend,
            ];
        });
    }

    /**
     * Get widget key prefix based on model type.
     */
    private function getWidgetKeyPrefix(string $modelClass): string
    {
        return match ($modelClass) {
            Store::class => 'ActiveStores',
            Couier::class => 'ActiveCouriers',
            default => 'Count'
        };
    }

    /**
     * Apply common filters to query.
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['store_id'])) {
            $query->where('store_id', $filters['store_id']);
        }

        if (!empty($filters['zone_id'])) {
            $query->where('zone_id', $filters['zone_id']);
        }
    }

    /**
     * Apply filters for store queries.
     */
    private function applyStoreFilters(Builder $query, array $filters): void
    {
        // Stores don't have store_id filter, but can add other filters if needed
        // Example: $query->where('type', $filters['store_type'] ?? null);
    }

    /**
     * Apply filters for courier queries.
     */
    private function applyCourierFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['store_id'])) {
            $query->where('store_id', $filters['store_id']);
        }

        if (!empty($filters['zone_id'])) {
            $query->where('zone_id', $filters['zone_id']);
        }
    }

    /**
     * Generate area chart data with caching.
     */
    protected function areaChart(array $filters = []): array
    {
        $cacheKey = 'dashboard_area_chart_' . md5(serialize($filters));

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($filters) {
            $query = Order::query();
            $this->applyFilters($query, $filters);

            $periodType = $filters['period'] ?? 'month';
            $period = $this->getPeriodDates($periodType);

            return $this->getSalesData($query, $periodType, $period);
        });
    }

    /**
     * Generate pie chart data for orders by section with caching.
     */
    protected function pieChart(array $filters = []): array
    {
        $cacheKey = 'dashboard_pie_chart_' . md5(serialize($filters));

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($filters) {
            $query = Order::query();

            $this->applyFilters($query, $filters);
            // Define the section mapping to match the provided labels
            $sectionMapping = Section::where('type', '!=', SectionTypeEnum::DELIVERY_COMPANY)
                ->get()
                ->pluck('name', 'id')
                ->toArray();
            // Get counts for each section
            $sectionCounts = $query
                ->join('stores', 'orders.store_id', '=', 'stores.id')
                ->selectRaw('stores.section_id, COUNT(*) as count')
                ->whereIn('stores.section_id', array_keys($sectionMapping))
                ->groupBy('stores.section_id')
                ->pluck('count', 'stores.section_id')
                ->toArray();
            // Prepare data array with names and values
            $data = [];
            foreach ($sectionMapping as $sectionId => $label) {
                $data[] = [
                    'name' => $label,
                    'value' => $sectionCounts[$sectionId] ?? 0,
                ];
            }

            return [
                'labels' => array_values($sectionMapping),
                'datasets' => [
                    'data' => $data,
                    'backgroundColor' => [
                        '#00835B',
                        '#1DBF73',
                        '#FF3B30',
                        '#FFE41F',
                        '#FFFFFF',
                    ],
                    'borderColor' => [
                        '#00835B',
                        '#1DBF73',
                        '#FF3B30',
                        '#FFE41F',
                        '#FFFFFF',
                    ],
                    'borderWidth' => 2,
                ],
            ];
        });
    }

    /**
     * Get sales data based on period type.
     */
    private function getSalesData(Builder $query, string $periodType, array $period): array
    {
        $baseQuery = $query->whereBetween('created_at', [
            $period['current_start'],
            $period['current_end']
        ]);

        switch ($periodType) {
            case 'day':
                $data = $baseQuery
                    ->selectRaw('HOUR(created_at) as hour, SUM(total_amount) as sales')
                    ->groupBy('hour')
                    ->orderBy('hour')
                    ->get()
                    ->map(fn($item) => [
                        'hour' => $item->hour,
                        'sales' => round($item->sales, 2)
                    ])
                    ->toArray();

                // Fill missing hours with 0
                $result = [];
                for ($hour = 0; $hour < 24; $hour++) {
                    $hourData = collect($data)->firstWhere('hour', $hour);
                    $result[] = [
                        'name' => Carbon::createFromTime($hour, 0, 0)->format('g A'), // e.g., "1 AM", "2 PM"
                        'amt' => $hourData ? $hourData['sales'] : 0
                    ];
                }
                return $result;

            case 'week':
                $data = $baseQuery
                    ->selectRaw('DAYOFWEEK(created_at) as day_num, DAYNAME(created_at) as day_name, SUM(total_amount) as sales')
                    ->groupByRaw('day_num, day_name')
                    ->orderBy('day_num')
                    ->get()
                    ->map(fn($item) => [
                        'day_num' => $item->day_num,
                        'sales' => round($item->sales, 2)
                    ])
                    ->toArray();

                // Fill all days of week
                $result = [];
                $daysOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

                foreach ($daysOfWeek as $index => $dayName) {
                    $dayNum = $index + 1; // DAYOFWEEK returns 1=Sunday, 2=Monday, etc.
                    $dayData = collect($data)->firstWhere('day_num', $dayNum);
                    $result[] = [
                        'name' => $dayName,
                        'amt' => $dayData ? $dayData['sales'] : 0
                    ];
                }
                return $result;

            case 'year':
                $data = $baseQuery
                    ->selectRaw('MONTH(created_at) as month_num, MONTHNAME(created_at) as month_name, SUM(total_amount) as sales')
                    ->groupByRaw('month_num, month_name')
                    ->orderBy('month_num')
                    ->get()
                    ->map(fn($item) => [
                        'month_num' => $item->month_num,
                        'sales' => round($item->sales, 2)
                    ])
                    ->toArray();

                // Fill all months
                $result = [];
                $months = [
                    'January',
                    'February',
                    'March',
                    'April',
                    'May',
                    'June',
                    'July',
                    'August',
                    'September',
                    'October',
                    'November',
                    'December'
                ];

                foreach ($months as $index => $monthName) {
                    $monthNum = $index + 1;
                    $monthData = collect($data)->firstWhere('month_num', $monthNum);
                    $result[] = [
                        'name' => $monthName,
                        'amt' => $monthData ? $monthData['sales'] : 0
                    ];
                }
                return $result;

            case 'month':
            default:
                $currentMonth = $period['current_start']->month;
                $currentYear = $period['current_start']->year;
                $daysInMonth = $period['current_start']->daysInMonth;

                $data = $baseQuery
                    ->selectRaw('DAY(created_at) as day, SUM(total_amount) as sales')
                    ->groupBy('day')
                    ->orderBy('day')
                    ->get()
                    ->map(fn($item) => [
                        'day' => $item->day,
                        'sales' => round($item->sales, 2)
                    ])
                    ->toArray();

                // Fill all days of the month
                $result = [];
                for ($day = 1; $day <= $daysInMonth; $day++) {
                    $dayData = collect($data)->firstWhere('day', $day);
                    $date = Carbon::create($currentYear, $currentMonth, $day);
                    $result[] = [
                        'name' => $date->format('M-d'), // e.g., "1st", "2nd", "15th", "31st"
                        'amt' => $dayData ? $dayData['sales'] : 0
                    ];
                }
                return $result;
        }
    }

    /**
     * Get period dates based on filter.
     */
    private function getPeriodDates(string $period = 'month'): array
    {
        $now = Carbon::now();

        return match ($period) {
            'day' => [
                'current_start' => $now->clone()->startOfDay(),
                'current_end' => $now->clone()->endOfDay(),
                'previous_start' => $now->clone()->subDay()->startOfDay(),
                'previous_end' => $now->clone()->subDay()->endOfDay(),
            ],
            'week' => [
                'current_start' => $now->clone()->startOfWeek(),
                'current_end' => $now->clone()->endOfWeek(),
                'previous_start' => $now->clone()->subWeek()->startOfWeek(),
                'previous_end' => $now->clone()->subWeek()->endOfWeek(),
            ],
            'year' => [
                'current_start' => $now->clone()->startOfYear(),
                'current_end' => $now->clone()->endOfYear(),
                'previous_start' => $now->clone()->subYear()->startOfYear(),
                'previous_end' => $now->clone()->subYear()->endOfYear(),
            ],
            default => [ // month
                'current_start' => $now->clone()->startOfMonth(),
                'current_end' => $now->clone()->endOfMonth(),
                'previous_start' => $now->clone()->subMonth()->startOfMonth(),
                'previous_end' => $now->clone()->subMonth()->endOfMonth(),
            ],
        };
    }

    /**
     * Calculate growth rate and trend.
     */
    private function calculateGrowthRate(float|int $current, float|int $previous): array
    {
        if ($previous > 0) {
            $rate = (($current - $previous) / $previous) * 100;
            $trend = match (true) {
                $rate > 0 => 'increment',
                $rate < 0 => 'decrement',
                default => 'no_change'
            };
        } elseif ($current > 0) {
            $rate = 100; // 100% growth from 0
            $trend = 'increment';
        } else {
            $rate = 0;
            $trend = 'no_change';
        }

        return [round($rate, 2), $trend];
    }

    /**
     * Validate and sanitize filters.
     */
    private function validateFilters(array $filters): array
    {
        $validated = [];

        if (!empty($filters['store_id']) && is_numeric($filters['store_id'])) {
            $validated['store_id'] = (int) $filters['store_id'];
        }

        if (!empty($filters['period']) && in_array($filters['period'], ['day', 'week', 'month', 'year'])) {
            $validated['period'] = $filters['period'];
        } else {
            $validated['period'] = 'month';
        }

        return $validated;
    }

    /**
     * Clear dashboard cache.
     * Can be called from command or admin panel.
     */
    public function clearCache(): bool
    {
        try {
            Cache::tags(['dashboard'])->flush();
            return true;
        } catch (\Exception $e) {
            // Log error if needed
            return false;
        }
    }
    /**
     * Get top stores based on filters.
     *
     * @param array $filters
     * @return array
     */
    private function topStores(array $filters = []): array
    {
        $cacheKey = 'dashboard_top_stores_' . md5(serialize($filters));
        try {
            return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($filters) {
                $period = $this->getPeriodDates($filters['period'] ?? 'month');

                $topStoresQuery = Store::query()
                    ->where('stores.status', StoreStatusEnum::APPROVED)
                    ->where('stores.is_active', true);

                if (!empty($filters['store_id'])) {
                    $topStoresQuery->where('stores.id', $filters['store_id']);
                }

                // Join orders with stores and calculate total sales per store
                $topStoresQuery->join('orders', 'stores.id', '=', 'orders.store_id')
                    ->select(
                        'stores.id',
                        \DB::raw('MAX(stores.status) as status'),
                        \DB::raw('MAX(stores.avg_rate) as avg_rate'),
                        \DB::raw('MAX(stores.is_active) as is_active'),
                        \DB::raw('MAX(stores.deleted_at) as deleted_at'),
                        \DB::raw('SUM(orders.total_amount) as total_sales'),
                        \DB::raw('COUNT(orders.id) as order_count')
                    )
                    ->whereNull('stores.deleted_at')
                    ->groupBy('stores.id');
                $topStores = $topStoresQuery->limit(10)->get();

                return $topStores->map(function ($store) {
                    return [
                        'id' => $store->id,
                        'name' => $store->name,
                        'total_sales' => round($store->total_sales, 2),
                        'order_count' => $store->order_count,
                        'avg_rate' => $store->avg_rate,
                        'symbol_currency' => $store->address?->zone?->city?->governorate?->country?->currency_symbol ?? 'EGP',
                    ];
                })->toArray();
            });
        } catch (\Exception $e) {
            return [];
        }
    }
protected function latestOrders(array $filters = []): array
{
    $cacheKey = 'dashboard_latest_orders_' . md5(serialize($filters));
    try {
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($filters) {
            $period = $this->getPeriodDates($filters['period'] ?? 'month');

            $latestOrdersQuery = Order::query();

            if (!empty($filters['store_id'])) {
                $latestOrdersQuery->where('orders.store_id', $filters['store_id']);
            }

            $latestOrdersQuery->join('stores', 'orders.store_id', '=', 'stores.id')
                ->select(
                    'orders.id',
                    'orders.order_number',
                    'orders.total_amount',
                    'orders.created_at',
                    'stores.id as store_id', 
                    'orders.status'
                )
                ->whereNull('orders.deleted_at')
                ->limit(10);

            $latestOrders = $latestOrdersQuery->get();

            return $latestOrders->map(function ($order) {
                $store = $order->store; 
                $storeName = $store->name;

                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $order->status->value,
                    'status_label' => OrderStatus::label($order->status->value),
                    'total_amount' => round($order->total_amount, 2),
                    'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                    'store_name' => $storeName, 
                    'symbol_currency' => $order->store->address?->zone?->city?->governorate?->country?->currency_symbol ?? 'EGP',
                ];
            })->toArray();
        });
    } catch (\Exception $e) {
        \Log::error('Error fetching latest orders: ' . $e->getMessage());
        return [];
    }
}

}
