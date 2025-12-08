<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use App\Enums\CouierAvaliableStatusEnum;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Modules\Order\Models\Order;
use Modules\Store\Models\Store;
use Modules\Couier\Models\Couier;

class DashboardController extends Controller
{
    use ApiResponse;

    /**
     * Handle the dashboard request.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request)
    {
        $filters = $request->only(['store_id', 'period']);

        $orderWidget = $this->orderWidget($filters);
        $salesWidget = $this->salesWidget($filters);
        $activeStoresWidget = $this->activeStoresWidget($filters);
        $activeCouriersWidget = $this->activeCouriersWidget($filters);

        $widget = [
            'order_widget' => $orderWidget,
            'sales_widget' => $salesWidget,
            'active_stores_widget' => $activeStoresWidget,
            'active_couriers_widget' => $activeCouriersWidget,
        ];

        return $this->successResponse([
            'widget' => $widget,
        ], __('message.success'));
    }

    /**
     * Get order-related widget data with caching and filtering.
     *
     * @param array $filters
     * @return array
     */
    protected function orderWidget(array $filters = [])
    {
        $cacheKey = 'dashboard_order_widget_' . md5(serialize($filters));

        return Cache::remember($cacheKey, 3600, function () use ($filters) {
            $query = Order::query();

            $this->applyFilters($query, $filters);

            $totalOrders = (clone $query)->count();

            $period = $this->getPeriodDates($filters['period'] ?? 'month');

            $periodOrders = (clone $query)->whereBetween('created_at', [$period['current_start'], $period['current_end']])->count();
            $previousPeriodOrders = (clone $query)->whereBetween('created_at', [$period['previous_start'], $period['previous_end']])->count();

            $periodRate = 0;
            $periodTrend = 'no_change';

            if ($previousPeriodOrders > 0) {
                $periodRate = (($periodOrders - $previousPeriodOrders) / $previousPeriodOrders) * 100;
                $periodTrend = $periodRate > 0 ? 'increment' : ($periodRate < 0 ? 'decrement' : 'no_change');
            } elseif ($periodOrders > 0) {
                $periodTrend = 'increment';
            }

            return [
                'totalOrders' => $totalOrders,
                'periodOrders' => $periodOrders,
                'periodRate' => round($periodRate, 2),
                'periodTrend' => $periodTrend,
            ];
        });
    }

    /**
     * Get sales widget data with caching and filtering.
     *
     * @param array $filters
     * @return array
     */
    protected function salesWidget(array $filters = [])
    {
        $cacheKey = 'dashboard_sales_widget_' . md5(serialize($filters));

        return Cache::remember($cacheKey, 3600, function () use ($filters) {
            $query = Order::query();

            $this->applyFilters($query, $filters);

            $totalSales = (clone $query)->sum('total_amount');

            $period = $this->getPeriodDates($filters['period'] ?? 'month');

            $periodSales = (clone $query)->whereBetween('created_at', [$period['current_start'], $period['current_end']])->sum('total_amount');
            $previousPeriodSales = (clone $query)->whereBetween('created_at', [$period['previous_start'], $period['previous_end']])->sum('total_amount');

            $periodRate = 0;
            $periodTrend = 'no_change';

            if ($previousPeriodSales > 0) {
                $periodRate = (($periodSales - $previousPeriodSales) / $previousPeriodSales) * 100;
                $periodTrend = $periodRate > 0 ? 'increment' : ($periodRate < 0 ? 'decrement' : 'no_change');
            } elseif ($periodSales > 0) {
                $periodTrend = 'increment';
            }

            return [
                'totalSales' => round($totalSales, 2),
                'periodSales' => round($periodSales, 2),
                'periodRate' => round($periodRate, 2),
                'periodTrend' => $periodTrend,
            ];
        });
    }

    /**
     * Get active stores widget data with caching and filtering.
     *
     * @param array $filters
     * @return array
     */
    protected function activeStoresWidget(array $filters = [])
    {
        $cacheKey = 'dashboard_active_stores_widget_' . md5(serialize($filters));

        return Cache::remember($cacheKey, 3600, function () use ($filters) {
            $query = Store::query();

            $this->applyStoreFilters($query, $filters);

            $activeStoresCount = (clone $query)->where('is_active', true)->count();

            $period = $this->getPeriodDates($filters['period'] ?? 'month');

            $periodStores = (clone $query)->whereBetween('created_at', [$period['current_start'], $period['current_end']])->count();
            $previousPeriodStores = (clone $query)->whereBetween('created_at', [$period['previous_start'], $period['previous_end']])->count();

            $periodRate = 0;
            $periodTrend = 'no_change';

            if ($previousPeriodStores > 0) {
                $periodRate = (($periodStores - $previousPeriodStores) / $previousPeriodStores) * 100;
                $periodTrend = $periodRate > 0 ? 'increment' : ($periodRate < 0 ? 'decrement' : 'no_change');
            } elseif ($periodStores > 0) {
                $periodTrend = 'increment';
            }

            return [
                'totalActiveStores' => $activeStoresCount,
                'periodStores' => $periodStores,
                'periodRate' => round($periodRate, 2),
                'periodTrend' => $periodTrend,
            ];
        });
    }

    /**
     * Get active couriers widget data with caching and filtering.
     *
     * @param array $filters
     * @return array
     */
    protected function activeCouriersWidget(array $filters = [])
    {
        $cacheKey = 'dashboard_active_couriers_widget_' . md5(serialize($filters));

        return Cache::remember($cacheKey, 3600, function () use ($filters) {
            $query = Couier::query();

            $this->applyCourierFilters($query, $filters);

            $activeCouriersCount = (clone $query)->where('avaliable_status', CouierAvaliableStatusEnum::AVAILABLE)->count();

            $period = $this->getPeriodDates($filters['period'] ?? 'month');

            $periodCouriers = (clone $query)->whereBetween('created_at', [$period['current_start'], $period['current_end']])->count();
            $previousPeriodCouriers = (clone $query)->whereBetween('created_at', [$period['previous_start'], $period['previous_end']])->count();

            $periodRate = 0;
            $periodTrend = 'no_change';

            if ($previousPeriodCouriers > 0) {
                $periodRate = (($periodCouriers - $previousPeriodCouriers) / $previousPeriodCouriers) * 100;
                $periodTrend = $periodRate > 0 ? 'increment' : ($periodRate < 0 ? 'decrement' : 'no_change');
            } elseif ($periodCouriers > 0) {
                $periodTrend = 'increment';
            }

            return [
                'totalActiveCouriers' => $activeCouriersCount,
                'periodCouriers' => $periodCouriers,
                'periodRate' => round($periodRate, 2),
                'periodTrend' => $periodTrend,
            ];
        });
    }

    /**
     * Apply common filters to query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     */
    private function applyFilters($query, array $filters)
    {
        if (!empty($filters['store_id'])) {
            $query->where('store_id', $filters['store_id']);
        }
    }

    /**
     * Apply filters for store queries.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     */
    private function applyStoreFilters($query, array $filters)
    {
        // Stores don't have store_id filter, but can add other filters if needed
    }

    /**
     * Apply filters for courier queries.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     */
    private function applyCourierFilters($query, array $filters)
    {
        if (!empty($filters['store_id'])) {
            $query->where('store_id', $filters['store_id']);
        }
    }

    /**
     * Get period dates based on filter.
     *
     * @param string $period
     * @return array
     */
    private function getPeriodDates(string $period = 'month'): array
    {
        $now = Carbon::now();

        switch ($period) {
            case 'day':
                return [
                    'current_start' => $now->copy()->startOfDay(),
                    'current_end' => $now->copy()->endOfDay(),
                    'previous_start' => $now->copy()->subDay()->startOfDay(),
                    'previous_end' => $now->copy()->subDay()->endOfDay(),
                ];
            case 'week':
                return [
                    'current_start' => $now->copy()->startOfWeek(),
                    'current_end' => $now->copy()->endOfWeek(),
                    'previous_start' => $now->copy()->subWeek()->startOfWeek(),
                    'previous_end' => $now->copy()->subWeek()->endOfWeek(),
                ];
            case 'year':
                return [
                    'current_start' => $now->copy()->startOfYear(),
                    'current_end' => $now->copy()->endOfYear(),
                    'previous_start' => $now->copy()->subYear()->startOfYear(),
                    'previous_end' => $now->copy()->subYear()->endOfYear(),
                ];
            case 'month':
            default:
                return [
                    'current_start' => $now->copy()->startOfMonth(),
                    'current_end' => $now->copy()->endOfMonth(),
                    'previous_start' => $now->copy()->subMonth()->startOfMonth(),
                    'previous_end' => $now->copy()->subMonth()->endOfMonth(),
                ];
        }
    }
}
