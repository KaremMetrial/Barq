<?php

namespace Modules\Vendor\Http\Controllers\Vendor;

use App\Helpers\CurrencyHelper;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Modules\Order\Models\Order;
use Modules\Balance\Models\Balance;
use App\Models\Transaction;
use Modules\Setting\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class VendorReportController extends Controller
{
    use ApiResponse;

    /**
     * Get comprehensive vendor reports including operational performance, financial info, and wallet data
     */
    public function getVendorReports(Request $request): JsonResponse
    {
        $vendor = Auth::guard('vendor')->user();

        // Get store ID from vendor
        $storeId = $vendor->store_id;
        if (!$storeId) {
            return $this->errorResponse(__('message.vendor_no_store'), 400);
        }

        $filterType = $request->input('filter_type', 'day');
        $chartFilterType = $request->input('chart_filter_type', 'day');

        $endDate = now();
        $startDate = $this->getStartDateBasedOnFilter($filterType, $endDate);

        try {
            // Get the store's currency
            $currencyInfo = $this->getStoreCurrencyInfo($storeId);

            $reportData = [
                'operational_performance' => $this->getOperationalPerformance($storeId, $startDate, $endDate, $filterType, $currencyInfo, $chartFilterType),
                'financial_information' => $this->getFinancialInformation($vendor, $currencyInfo),
                'wallet_data' => $this->getWalletData($vendor, $currencyInfo),
                'transactions' => $this->getRecentTransactions($vendor, $currencyInfo),
                'currency' => $currencyInfo['symbol']
            ];

            return $this->successResponse($reportData, __('message.success'));
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    protected function changeDirection(float $percent): string
    {
        return $percent > 0 ? 'up' : ($percent < 0 ? 'down' : 'flat');
    }

    /**
     * Get operational performance data
     */
    protected function getOperationalPerformance(int $storeId, $startDate, $endDate, string $filterType, array $currencyInfo, string $chartFilterType): array
    {
        $factor = $currencyInfo['factor'];

        // Get previous period dates for comparison
        $previousPeriodDates = $this->getPreviousPeriodDates($filterType, $startDate);
        $previousStartDate = $previousPeriodDates['start'];
        $previousEndDate = $previousPeriodDates['end'];

        $previousChartPeriodDates = $this->getPreviousPeriodDates($chartFilterType, $startDate);
        $previousChartStartDate = $previousChartPeriodDates['start'];
        $previousChartEndDate = $previousChartPeriodDates['end'];

        // Get comparison label
        $compareLabel = $this->getComparisonLabel($filterType);

        // Current period metrics
        $totalSalesCurrent = Order::where('store_id', $storeId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'delivered')
            ->sum('total_amount');

        $orderCountCurrent = Order::where('store_id', $storeId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'delivered')
            ->count();

        $avgOrderValueCurrent = $orderCountCurrent > 0
            ? $totalSalesCurrent / $orderCountCurrent
            : 0;

        // Previous period metrics
        $totalSalesPrevious = Order::where('store_id', $storeId)
            ->whereBetween('created_at', [$previousStartDate, $previousEndDate])
            ->where('status', 'delivered')
            ->sum('total_amount');

        $orderCountPrevious = Order::where('store_id', $storeId)
            ->whereBetween('created_at', [$previousStartDate, $previousEndDate])
            ->where('status', 'delivered')
            ->count();

        $avgOrderValuePrevious = $orderCountPrevious > 0
            ? $totalSalesPrevious / $orderCountPrevious
            : 0;

        // Calculate percentage changes
        $salesChangePercent = $totalSalesPrevious > 0
            ? (($totalSalesCurrent - $totalSalesPrevious) / $totalSalesPrevious) * 100
            : ($totalSalesCurrent > 0 ? 100 : 0);

        $orderCountChangePercent = $orderCountPrevious > 0
            ? (($orderCountCurrent - $orderCountPrevious) / $orderCountPrevious) * 100
            : ($orderCountCurrent > 0 ? 100 : 0);

        $avgOrderValueChangePercent = $avgOrderValuePrevious > 0
            ? (($avgOrderValueCurrent - $avgOrderValuePrevious) / $avgOrderValuePrevious) * 100
            : ($avgOrderValueCurrent > 0 ? 100 : 0);

        // Get peak analysis based on filter type
        $peakAnalysis = $this->getPeakAnalysis($storeId, $startDate, $endDate, $filterType);
        $peakHourRange = $peakAnalysis['peak_range'] ?? "N/A";
        $peakHourChangePercent = $peakAnalysis['change_percent'] ?? 0;

        return [
            'daily_metrics' => [
                'total_sales' => [
                    'value' => (int) $totalSalesCurrent,
                    'change_percent' => (float) $salesChangePercent,
                    'change_direction' => $this->changeDirection($salesChangePercent),
                    'compare_to' => $compareLabel,
                    'currency' => $currencyInfo['symbol'],
                    'currency_factor' => $factor,
                ],
                'order_count' => [
                    'value' => (int) $orderCountCurrent,
                    'change_percent' => (float) $orderCountChangePercent,
                    'change_direction' => $this->changeDirection($orderCountChangePercent),
                    'compare_to' => $compareLabel,
                ],
                'average_order_value' => [
                    'value' => (int) $avgOrderValueCurrent,
                    'change_percent' => (float) $avgOrderValueChangePercent,
                    'change_direction' => $this->changeDirection($avgOrderValueChangePercent),
                    'compare_to' => $compareLabel,
                    'currency' => $currencyInfo['symbol'],
                    'currency_factor' => $factor,
                ],
                'peak_hours' => [
                    'value' => $peakHourRange,
                    'change_percent' => (float) $peakHourChangePercent,
                    'change_direction' => $this->changeDirection($peakHourChangePercent),
                    'compare_to' => $compareLabel,
                ],
            ],
            'weekly_sales_chart' => $this->getWeeklySalesChart($storeId,$previousChartStartDate,$previousChartEndDate,$chartFilterType),
            'peak_hour_analysis' => $this->getPeakHoursSlots($storeId, $startDate, $endDate, $filterType),
        ];
    }

    /**
     * Get weekly sales chart data based on filter type
     */
    protected function getWeeklySalesChart(int $storeId, Carbon $startDate, Carbon $endDate, string $filterType): array
    {
        // Get data based on filter type
        $chartData = [];
        $maxValue = 0;

        switch ($filterType) {
            case 'day':
                // Hourly data for day filter
                $hourlyData = Order::where('store_id', $storeId)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->where('status', 'delivered')
                    ->selectRaw('HOUR(created_at) as hour, SUM(total_amount) as total_sales, COUNT(*) as order_count')
                    ->groupBy('hour')
                    ->orderBy('hour')
                    ->get()
                    ->keyBy('hour');

                $hourLabels = [
                    '00:00', '01:00', '02:00', '03:00', '04:00', '05:00', '06:00', '07:00',
                    '08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00',
                    '16:00', '17:00', '18:00', '19:00', '20:00', '21:00', '22:00', '23:00'
                ];

                $daysMap = [];
                foreach ($hourLabels as $index => $hour) {
                    $daysMap[$hour] = $hour;
                }

                foreach ($daysMap as $hourKey => $hourLabel) {
                    $hour = (int) substr($hourKey, 0, 2);
                    $data = $hourlyData[$hour] ?? null;
                    $total = $data ? (int)$data->total_sales : 0;

                    $maxValue = max($maxValue, $total);

                    $chartData[] = [
                        'day_key' => 'hour_' . $hour,
                        'day_name' => $hourLabel,
                        'total_sales' => (int) $total,
                        'is_peak' => false,
                    ];
                }
                break;

            case 'week':
                // Daily data for week filter (last 7 days)
                $daysMap = [
                    'sat' => 'السبت',
                    'sun' => 'الأحد',
                    'mon' => 'الإثنين',
                    'tue' => 'الثلاثاء',
                    'wed' => 'الأربعاء',
                    'thu' => 'الخميس',
                    'fri' => 'الجمعة',
                ];

                $currentDate = $startDate->copy();
                $dayIndex = 0;

                while ($currentDate->lte($endDate)) {
                    $dayOfWeek = strtolower($currentDate->format('D'));
                    $dayKey = array_keys($daysMap)[$dayIndex % 7] ?? $dayOfWeek;
                    $dayName = $daysMap[$dayKey] ?? $currentDate->translatedFormat('l');

                    $total = Order::where('store_id', $storeId)
                        ->whereDate('created_at', $currentDate)
                        ->where('status', 'delivered')
                        ->sum('total_amount');

                    $maxValue = max($maxValue, $total);

                    $chartData[] = [
                        'day_key' => $dayKey,
                        'day_name' => $dayName,
                        'total_sales' => (int) $total,
                        'is_peak' => false,
                    ];

                    $currentDate->addDay();
                    $dayIndex++;

                    // Limit to 7 days for week view
                    if ($dayIndex >= 7) break;
                }
                break;

            case 'month':
                // Weekly data for month filter (last 4 weeks)
                $currentWeekStart = $startDate->copy();
                $weekNumber = 1;

                while ($currentWeekStart->lte($endDate)) {
                    $weekEnd = $currentWeekStart->copy()->addDays(6);
                    if ($weekEnd->gt($endDate)) {
                        $weekEnd = $endDate->copy();
                    }

                    $total = Order::where('store_id', $storeId)
                        ->whereBetween('created_at', [$currentWeekStart, $weekEnd])
                        ->where('status', 'delivered')
                        ->sum('total_amount');

                    $maxValue = max($maxValue, $total);

                    $chartData[] = [
                        'day_key' => 'week_' . $weekNumber,
                        'day_name' => 'أسبوع ' . $weekNumber,
                        'total_sales' => (int) $total,
                        'is_peak' => false,
                    ];

                    $currentWeekStart->addWeek();
                    $weekNumber++;

                    // Limit to 5 weeks for month view
                    if ($weekNumber > 5) break;
                }
                break;

            case 'year':
                // Monthly data for year filter (last 12 months)
                $monthsMap = [
                    'jan' => 'يناير',
                    'feb' => 'فبراير',
                    'mar' => 'مارس',
                    'apr' => 'أبريل',
                    'may' => 'مايو',
                    'jun' => 'يونيو',
                    'jul' => 'يوليو',
                    'aug' => 'أغسطس',
                    'sep' => 'سبتمبر',
                    'oct' => 'أكتوبر',
                    'nov' => 'نوفمبر',
                    'dec' => 'ديسمبر'
                ];

                $currentMonth = $startDate->copy()->startOfMonth();
                $monthIndex = 0;

                while ($currentMonth->lte($endDate) && $monthIndex < 12) {
                    $monthEnd = $currentMonth->copy()->endOfMonth();
                    if ($monthEnd->gt($endDate)) {
                        $monthEnd = $endDate->copy();
                    }

                    $total = Order::where('store_id', $storeId)
                        ->whereBetween('created_at', [$currentMonth, $monthEnd])
                        ->where('status', 'delivered')
                        ->sum('total_amount');

                    $maxValue = max($maxValue, $total);

                    $monthKey = strtolower($currentMonth->format('M'));
                    $monthName = $monthsMap[$monthKey] ?? $currentMonth->translatedFormat('F');

                    $chartData[] = [
                        'day_key' => $monthKey,
                        'day_name' => $monthName,
                        'total_sales' => (int) $total,
                        'is_peak' => false,
                    ];

                    $currentMonth->addMonth();
                    $monthIndex++;
                }
                break;
        }

        // mark peak day/hour/week/month
        foreach ($chartData as &$item) {
            if ($item['total_sales'] === $maxValue && $maxValue > 0) {
                $item['is_peak'] = true;
                break;
            }
        }

        return [
            'currency' => 'KWD', // Keep as KWD or get from currencyInfo
            'max_value' => (int) $maxValue,
            'days' => $chartData,
        ];
    }

    /**
     * Get peak hours slots based on filter type
     */
    protected function getPeakHoursSlots(int $storeId, Carbon $startDate, Carbon $endDate, string $filterType): array
    {
        switch ($filterType) {
            case 'day':
                // Original peak hours slots for day filter
                $slots = [
                    ['from' => 9,  'to' => 12],
                    ['from' => 12, 'to' => 15],
                    ['from' => 15, 'to' => 18],
                    ['from' => 18, 'to' => 21],
                    ['from' => 21, 'to' => 24],
                    ['from' => 0,  'to' => 3],
                ];

                $result = [];
                $maxOrders = 0;

                foreach ($slots as $slot) {
                    $query = Order::where('store_id', $storeId)
                        ->whereBetween('created_at', [$startDate, $endDate])
                        ->where('status', 'delivered');

                    // slot crosses midnight
                    if ($slot['from'] > $slot['to']) {
                        $query->where(function ($q) use ($slot) {
                            $q->whereRaw('HOUR(created_at) >= ?', [$slot['from']])
                              ->orWhereRaw('HOUR(created_at) < ?', [$slot['to']]);
                        });
                    } else {
                        $query->whereRaw(
                            'HOUR(created_at) >= ? AND HOUR(created_at) < ?',
                            [$slot['from'], $slot['to']]
                        );
                    }

                    $count = $query->count();
                    $maxOrders = max($maxOrders, $count);

                    $result[] = [
                        'from' => sprintf('%02d:00', $slot['from']),
                        'to'   => sprintf('%02d:00', $slot['to']),
                        'orders_count' => $count,
                        'is_peak' => false,
                    ];
                }

                // mark peak
                foreach ($result as &$item) {
                    if ($item['orders_count'] === $maxOrders && $maxOrders > 0) {
                        $item['is_peak'] = true;
                        break;
                    }
                }

                return $result;

            case 'week':
                // Daily slots for week filter
                $days = [
                    ['key' => 'sat', 'name' => 'السبت', 'from' => 'السبت', 'to' => ''],
                    ['key' => 'sun', 'name' => 'الأحد', 'from' => 'الأحد', 'to' => ''],
                    ['key' => 'mon', 'name' => 'الإثنين', 'from' => 'الإثنين', 'to' => ''],
                    ['key' => 'tue', 'name' => 'الثلاثاء', 'from' => 'الثلاثاء', 'to' => ''],
                    ['key' => 'wed', 'name' => 'الأربعاء', 'from' => 'الأربعاء', 'to' => ''],
                    ['key' => 'thu', 'name' => 'الخميس', 'from' => 'الخميس', 'to' => ''],
                    ['key' => 'fri', 'name' => 'الجمعة', 'from' => 'الجمعة', 'to' => ''],
                ];

                $result = [];
                $maxOrders = 0;

                foreach ($days as $day) {
                    // Map day key to day number (MySQL: 1=Sunday, 7=Saturday)
                    $dayNumberMap = ['sat' => 7, 'sun' => 1, 'mon' => 2, 'tue' => 3, 'wed' => 4, 'thu' => 5, 'fri' => 6];
                    $dayNumber = $dayNumberMap[$day['key']] ?? 0;

                    $count = Order::where('store_id', $storeId)
                        ->whereBetween('created_at', [$startDate, $endDate])
                        ->where('status', 'delivered')
                        ->whereRaw('DAYOFWEEK(created_at) = ?', [$dayNumber])
                        ->count();

                    $maxOrders = max($maxOrders, $count);

                    $result[] = [
                        'from' => $day['from'],
                        'to'   => $day['to'],
                        'orders_count' => $count,
                        'is_peak' => false,
                    ];
                }

                // mark peak
                foreach ($result as &$item) {
                    if ($item['orders_count'] === $maxOrders && $maxOrders > 0) {
                        $item['is_peak'] = true;
                        break;
                    }
                }

                return $result;

            case 'month':
                // Weekly slots for month filter
                $weeks = [];
                $maxOrders = 0;

                $currentWeekStart = $startDate->copy();
                $weekNumber = 1;

                while ($currentWeekStart->lte($endDate)) {
                    $weekEnd = $currentWeekStart->copy()->addDays(6);
                    if ($weekEnd->gt($endDate)) {
                        $weekEnd = $endDate->copy();
                    }

                    $count = Order::where('store_id', $storeId)
                        ->whereBetween('created_at', [$currentWeekStart, $weekEnd])
                        ->where('status', 'delivered')
                        ->count();

                    $maxOrders = max($maxOrders, $count);

                    $weeks[] = [
                        'from' => 'أسبوع ' . $weekNumber,
                        'to'   => '',
                        'orders_count' => $count,
                        'is_peak' => false,
                    ];

                    $currentWeekStart->addWeek();
                    $weekNumber++;

                    if ($weekNumber > 5) break;
                }

                // mark peak
                foreach ($weeks as &$week) {
                    if ($week['orders_count'] === $maxOrders && $maxOrders > 0) {
                        $week['is_peak'] = true;
                        break;
                    }
                }

                return $weeks;

            case 'year':
                // Monthly slots for year filter
                $monthsMap = [
                    ['key' => 'jan', 'name' => 'يناير', 'from' => 'يناير', 'to' => ''],
                    ['key' => 'feb', 'name' => 'فبراير', 'from' => 'فبراير', 'to' => ''],
                    ['key' => 'mar', 'name' => 'مارس', 'from' => 'مارس', 'to' => ''],
                    ['key' => 'apr', 'name' => 'أبريل', 'from' => 'أبريل', 'to' => ''],
                    ['key' => 'may', 'name' => 'مايو', 'from' => 'مايو', 'to' => ''],
                    ['key' => 'jun', 'name' => 'يونيو', 'from' => 'يونيو', 'to' => ''],
                    ['key' => 'jul', 'name' => 'يوليو', 'from' => 'يوليو', 'to' => ''],
                    ['key' => 'aug', 'name' => 'أغسطس', 'from' => 'أغسطس', 'to' => ''],
                    ['key' => 'sep', 'name' => 'سبتمبر', 'from' => 'سبتمبر', 'to' => ''],
                    ['key' => 'oct', 'name' => 'أكتوبر', 'from' => 'أكتوبر', 'to' => ''],
                    ['key' => 'nov', 'name' => 'نوفمبر', 'from' => 'نوفمبر', 'to' => ''],
                    ['key' => 'dec', 'name' => 'ديسمبر', 'from' => 'ديسمبر', 'to' => ''],
                ];

                $result = [];
                $maxOrders = 0;

                $currentMonth = $startDate->copy()->startOfMonth();
                $monthIndex = 0;

                while ($currentMonth->lte($endDate) && $monthIndex < 12) {
                    $monthEnd = $currentMonth->copy()->endOfMonth();
                    if ($monthEnd->gt($endDate)) {
                        $monthEnd = $endDate->copy();
                    }

                    $count = Order::where('store_id', $storeId)
                        ->whereBetween('created_at', [$currentMonth, $monthEnd])
                        ->where('status', 'delivered')
                        ->count();

                    $maxOrders = max($maxOrders, $count);

                    $monthKey = strtolower($currentMonth->format('M'));
                    $monthName = '';
                    foreach ($monthsMap as $month) {
                        if ($month['key'] === $monthKey) {
                            $monthName = $month['name'];
                            break;
                        }
                    }

                    $result[] = [
                        'from' => $monthName,
                        'to'   => '',
                        'orders_count' => $count,
                        'is_peak' => false,
                    ];

                    $currentMonth->addMonth();
                    $monthIndex++;
                }

                // mark peak
                foreach ($result as &$item) {
                    if ($item['orders_count'] === $maxOrders && $maxOrders > 0) {
                        $item['is_peak'] = true;
                        break;
                    }
                }

                return $result;

            default:
                return [];
        }
    }

    /**
     * Get peak analysis based on filter type
     */
    protected function getPeakAnalysis(int $storeId, Carbon $startDate, Carbon $endDate, string $filterType): array
    {
        $query = Order::where('store_id', $storeId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'delivered');

        switch ($filterType) {
            case 'day':
                $query->selectRaw('HOUR(created_at) as time_unit, COUNT(*) as order_count, SUM(total_amount) as total_sales')
                    ->groupBy('time_unit')
                    ->orderByDesc('order_count');
                break;
            case 'week':
                $query->selectRaw('DAYOFWEEK(created_at) as time_unit, COUNT(*) as order_count, SUM(total_amount) as total_sales')
                    ->groupBy('time_unit')
                    ->orderByDesc('order_count');
                break;
            case 'month':
                $query->selectRaw('WEEK(created_at, 3) as time_unit, COUNT(*) as order_count, SUM(total_amount) as total_sales')
                    ->groupBy('time_unit')
                    ->orderByDesc('order_count');
                break;
            case 'year':
                $query->selectRaw('MONTH(created_at) as time_unit, COUNT(*) as order_count, SUM(total_amount) as total_sales')
                    ->groupBy('time_unit')
                    ->orderByDesc('order_count');
                break;
        }

        $peakData = $query->first();

        if (!$peakData) {
            return ['peak_range' => 'N/A', 'change_percent' => 0];
        }

        // Format peak range
        $peakRange = $this->formatPeakRange($peakData->time_unit, $filterType);

        // Get previous period for comparison
        $previousPeriodDates = $this->getPreviousPeriodDates($filterType, $startDate);
        $previousPeakData = Order::where('store_id', $storeId)
            ->whereBetween('created_at', [$previousPeriodDates['start'], $previousPeriodDates['end']])
            ->where('status', 'delivered');

        switch ($filterType) {
            case 'day':
                $previousPeakData->selectRaw('HOUR(created_at) as time_unit, COUNT(*) as order_count')
                    ->groupBy('time_unit')
                    ->orderByDesc('order_count');
                break;
            case 'week':
                $previousPeakData->selectRaw('DAYOFWEEK(created_at) as time_unit, COUNT(*) as order_count')
                    ->groupBy('time_unit')
                    ->orderByDesc('order_count');
                break;
            case 'month':
                $previousPeakData->selectRaw('WEEK(created_at, 3) as time_unit, COUNT(*) as order_count')
                    ->groupBy('time_unit')
                    ->orderByDesc('order_count');
                break;
            case 'year':
                $previousPeakData->selectRaw('MONTH(created_at) as time_unit, COUNT(*) as order_count')
                    ->groupBy('time_unit')
                    ->orderByDesc('order_count');
                break;
        }

        $previousData = $previousPeakData->first();
        $previousCount = $previousData ? (float)$previousData->order_count : 0;

        $changePercent = $previousCount > 0
            ? (($peakData->order_count - $previousCount) / $previousCount) * 100
            : ($peakData->order_count > 0 ? 100 : 0);

        return [
            'peak_range' => $peakRange,
            'change_percent' => $changePercent
        ];
    }

    /**
     * Format peak range based on filter type
     */
    protected function formatPeakRange($timeUnit, string $filterType): string
    {
        switch ($filterType) {
            case 'day':
                return sprintf('%02d:00 - %02d:00', $timeUnit, ($timeUnit + 1) % 24);
            case 'week':
                $days = ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
                return $days[$timeUnit - 1] ?? 'N/A';
            case 'month':
                return "أسبوع " . $timeUnit;
            case 'year':
                $months = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو',
                          'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
                return $months[$timeUnit - 1] ?? 'N/A';
            default:
                return 'N/A';
        }
    }

    /**
     * Get previous period dates for comparison
     */
    protected function getPreviousPeriodDates(string $filterType, Carbon $currentStartDate): array
    {
        $duration = $currentStartDate->diffInDays(now());

        switch ($filterType) {
            case 'day':
                $previousStart = $currentStartDate->copy()->subDay();
                $previousEnd = $currentStartDate->copy()->subSecond();
                break;
            case 'week':
                $previousStart = $currentStartDate->copy()->subDays(7);
                $previousEnd = $currentStartDate->copy()->subSecond();
                break;
            case 'month':
                $previousStart = $currentStartDate->copy()->subDays(30);
                $previousEnd = $currentStartDate->copy()->subSecond();
                break;
            case 'year':
                $previousStart = $currentStartDate->copy()->subDays(365);
                $previousEnd = $currentStartDate->copy()->subSecond();
                break;
            default:
                $previousStart = $currentStartDate->copy()->subDay();
                $previousEnd = $currentStartDate->copy()->subSecond();
        }

        return [
            'start' => $previousStart,
            'end' => $previousEnd
        ];
    }

    /**
     * Get comparison label based on filter type
     */
    protected function getComparisonLabel(string $filterType): string
    {
        switch ($filterType) {
            case 'week':
                return 'الأسبوع الماضي';
            case 'month':
                return 'الشهر الماضي';
            case 'year':
                return 'العام الماضي';
            case 'day':
            default:
                return 'أمس';
        }
    }

    /**
     * Get start date based on filter type
     */
    protected function getStartDateBasedOnFilter(string $filterType, Carbon $endDate): Carbon
    {
        switch ($filterType) {
            case 'week':
                return $endDate->copy()->subDays(7);
            case 'month':
                return $endDate->copy()->subDays(30);
            case 'year':
                return $endDate->copy()->subDays(365);
            case 'day':
            default:
                return $endDate->copy()->subDay();
        }
    }

    protected function buildMetric(
        float|int $value,
        float $changePercent,
        ?string $currencySymbol = null,
        bool $isMoney = false,
        string $compareTo = 'أمس'
    ): array {
        $direction = $changePercent > 0
            ? 'up'
            : ($changePercent < 0 ? 'down' : 'flat');

        return [
            'value' => $value,
            'formatted' => $isMoney
                ? trim(number_format($value, 2)) . ' ' . $currencySymbol
                : (string) $value,
            'change_percent' => abs(round($changePercent, 2)),
            'change_direction' => $direction,
            'compare_to' => $compareTo,
        ];
    }

    /**
     * Get financial information
     */
    protected function getFinancialInformation($vendor, array $currencyInfo): array
    {
        $factor = $currencyInfo['factor'];
        $balance = Balance::where('balanceable_id', $vendor->id)
            ->where('balanceable_type', get_class($vendor))
            ->first();

        return [
            'wallet_balance' => [
                'total_balance' => $balance ? (int) $balance->total_balance : 0,
                'available_balance' => $balance ? (int) $balance->available_balance : 0,
                'pending_balance' => $balance ? (int) $balance->pending_balance : 0,
                'currency' => $currencyInfo['symbol'],
                'currency_factor' => $factor,
            ],
            'commissions' => [
                'total_paid' => 0,
                'currency' => $currencyInfo['symbol']
            ]
        ];
    }

    /**
     * Get wallet data
     */
    protected function getWalletData($vendor, array $currencyInfo): array
    {
        $factor = $currencyInfo['factor'];
        $balance = Balance::where('balanceable_id', $vendor->store_id)
            ->where('balanceable_type', 'store')
            ->first();

        if (!$balance) {
            return [
                'total_balance' => 0,
                'available_for_withdrawal' => 0,
                'currency' => $currencyInfo['symbol']
            ];
        }

        return [
            'total_balance' => (int) $balance->total_balance,
            'available_for_withdrawal' => (int) $balance->available_balance,
            'currency' => $currencyInfo['symbol'],
            'currency_factor' => $factor,
        ];
    }

    /**
     * Get recent transactions
     */
    protected function getRecentTransactions($vendor, array $currencyInfo): array
    {
        $factor = $currencyInfo['factor'];
        $transactions = Transaction::where(function($query) use ($vendor) {
            $query->where('transactionable_type', 'store')
                  ->where('transactionable_id', $vendor->store_id);
        })
        ->orderBy('created_at', 'desc')
        ->limit(10)
        ->get();

        return $transactions->map(function ($transaction) use ($factor) {
            return [
                'id' => $transaction->id,
                'type' => $transaction->type,
                'amount' => (int) $transaction->amount,
                'currency' => $transaction->currency,
                'currency_factor' => $factor,
                'description' => $transaction->description,
                'created_at' => $transaction->created_at->format('Y-m-d H:i:s'),
                'formatted_date' => $transaction->created_at->translatedFormat('d M Y, h:i A')
            ];
        })->all();
    }

    /**
     * Get the store's currency from settings or use default
     */
    protected function getStoreCurrencyInfo(int $storeId): array
    {
        $store = \Modules\Store\Models\Store::find($storeId);

        return [
            'symbol' => $store->currency_symbol ?? 'EGP',
            'factor' => $store->getCurrencyFactor(),
        ];
    }
}
