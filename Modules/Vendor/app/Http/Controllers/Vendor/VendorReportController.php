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

        // Get date range from request (default: last 7 days)
        $endDate = $request->input('end_date', now());
        $startDate = $request->input('start_date', now()->subDays(6));

        try {
            // Get the store's currency
            $currencyInfo = $this->getStoreCurrencyInfo($storeId);

            $reportData = [
                'operational_performance' => $this->getOperationalPerformance($storeId, $startDate, $endDate, $currencyInfo),
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
    protected function getOperationalPerformance(int $storeId, $startDate, $endDate, array $currencyInfo): array
    {
        $factor = $currencyInfo['factor'];
        // Get daily sales metrics
        $today = now();
        $yesterday = now()->subDay();

        // Total sales for today
        $totalSalesToday = Order::where('store_id', $storeId)
            ->whereDate('created_at', $today)
            ->where('status', 'delivered')
            ->sum('total_amount');

        // Order count for today
        $orderCountToday = Order::where('store_id', $storeId)
            ->whereDate('created_at', $today)
            ->where('status', 'delivered')
            ->count();

        // Average order value for today
        $avgOrderValueToday = $orderCountToday > 0
            ? $totalSalesToday / $orderCountToday
            : 0;

        // Peak hours analysis (group by hour)
        $peakHours = Order::where('store_id', $storeId)
            ->whereDate('created_at', $today)
            ->where('status', 'delivered')
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as order_count, SUM(total_amount) as total_sales')
            ->groupBy('hour')
            ->orderByDesc('order_count')
            ->first();

        $peakHour = $peakHours ? $peakHours->hour : null;
        $peakHourRange = $peakHour !== null ? "{$peakHour}:00 - " . ($peakHour + 1) . ":00" : "N/A";

        // Initialize peak hour change percent variable
        $peakHourChangePercent = 0;

        // Get historical data for peak hour comparison (last 7 days, same hour)
        if ($peakHour !== null && $peakHours) {
            $historicalPeakHourData = Order::where('store_id', $storeId)
                ->where('status', 'delivered')
                ->whereBetween('created_at', [now()->subDays(7), now()->subDay()])
                ->selectRaw('DATE(created_at) as date, HOUR(created_at) as hour, COUNT(*) as order_count, SUM(total_amount) as total_sales')
                ->groupBy('date', 'hour')
                ->get();

            // Filter historical data for the same peak hour
            $samePeakHourHistorical = $historicalPeakHourData->filter(function ($item) use ($peakHour) {
                return $item->hour == $peakHour;
            });

            if ($samePeakHourHistorical->isNotEmpty()) {
                // Calculate average historical performance for this hour
                $avgHistoricalOrderCount = $samePeakHourHistorical->avg('order_count');
                $avgHistoricalSales = $samePeakHourHistorical->avg('total_sales');

                // Calculate percentage change based on order count
                if ($avgHistoricalOrderCount > 0) {
                    $orderCountChange = (($peakHours->order_count - $avgHistoricalOrderCount) / $avgHistoricalOrderCount) * 100;
                    $salesChange = (($peakHours->total_sales - $avgHistoricalSales) / $avgHistoricalSales) * 100;

                    // Use the more significant change (absolute value)
                    $peakHourChangePercent = abs($orderCountChange) > abs($salesChange) ? $orderCountChange : $salesChange;
                }
            }
        }

        // Weekly sales data for chart
        $weeklySales = Order::where('store_id', $storeId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'delivered')
            ->selectRaw('DATE(created_at) as date, SUM(total_amount) as total_sales, COUNT(*) as order_count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Calculate percentage changes from yesterday
        $totalSalesYesterday = Order::where('store_id', $storeId)
            ->whereDate('created_at', $yesterday)
            ->where('status', 'delivered')
            ->sum('total_amount');

        $orderCountYesterday = Order::where('store_id', $storeId)
            ->whereDate('created_at', $yesterday)
            ->where('status', 'delivered')
            ->count();

        $salesChangePercent = $totalSalesYesterday > 0
            ? (($totalSalesToday - $totalSalesYesterday) / $totalSalesYesterday) * 100
            : 0;

        $orderCountChangePercent = $orderCountYesterday > 0
            ? (($orderCountToday - $orderCountYesterday) / $orderCountYesterday) * 100
            : 0;

        $avgOrderValueYesterday = $orderCountYesterday > 0
            ? $totalSalesYesterday / $orderCountYesterday
            : 0;

        $avgOrderValueChangePercent = $avgOrderValueYesterday > 0
            ? (($avgOrderValueToday - $avgOrderValueYesterday) / $avgOrderValueYesterday) * 100
            : 0;

        // Format weekly sales for chart
        $weeklySalesChart = $weeklySales->map(function ($item) use ($factor) {
            return [
                'date' => $item->date,
                'total_sales' => CurrencyHelper::fromMinorUnits($item->total_sales, $factor),
                'order_count' => (int) $item->order_count,
                'day_name' => Carbon::parse($item->date)->translatedFormat('l')
            ];
        });

        return [
            'daily_metrics' => [
                'total_sales' => [
                    'value' => CurrencyHelper::fromMinorUnits($totalSalesToday, $factor),
                    'change_percent' => (float) $salesChangePercent,
                    'change_direction' => $this->changeDirection($salesChangePercent),
                    'compare_to' => 'أمس',
                    'currency' => $currencyInfo['symbol']
                ],

             'order_count' => [
    'value' => (int) $orderCountToday,
    'change_percent' => (float) $orderCountChangePercent,
    'change_direction' => $this->changeDirection($orderCountChangePercent),
    'compare_to' => 'أمس',
],

              'average_order_value' => [
    'value' => CurrencyHelper::fromMinorUnits($avgOrderValueToday, $factor),
    'change_percent' => (float) $avgOrderValueChangePercent,
    'change_direction' => $this->changeDirection($avgOrderValueChangePercent),
    'compare_to' => 'أمس',
    'currency' => $currencyInfo['symbol']
],
'peak_hours' => [
    'value' => $peakHourRange,
    'change_percent' => (float) $peakHourChangePercent,
    'change_direction' => $this->changeDirection($peakHourChangePercent),
    'compare_to' => 'متوسط آخر 7 أيام',
],

            ],
            'weekly_sales_chart' => $this->getWeeklySalesChart(
                $storeId,
                now()->startOfWeek(Carbon::SATURDAY)
            ),
            'peak_hour_analysis' => $this->getPeakHoursSlots($storeId, $today),
        ];
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

protected function getWeeklySalesChart(int $storeId, Carbon $weekStart): array
{
    $daysMap = [
        'sat' => 'السبت',
        'sun' => 'الأحد',
        'mon' => 'الإثنين',
        'tue' => 'الثلاثاء',
        'wed' => 'الأربعاء',
        'thu' => 'الخميس',
        'fri' => 'الجمعة',
    ];

    $data = [];
    $maxValue = 0;

    foreach ($daysMap as $dayKey => $dayName) {
        $date = $weekStart->copy()->next($dayKey);

        $total = Order::where('store_id', $storeId)
            ->whereDate('created_at', $date)
            ->where('status', 'delivered')
            ->sum('total_amount'); // minor units if you use them

        $maxValue = max($maxValue, $total);

        $data[] = [
            'day_key' => $dayKey,
            'day_name' => $dayName,
            'total_sales' => (int) $total,
            'is_peak' => false,
        ];
    }

    // mark peak day
    foreach ($data as &$day) {
        if ($day['total_sales'] === $maxValue && $maxValue > 0) {
            $day['is_peak'] = true;
            break;
        }
    }

    return [
        'currency' => 'KWD',
        'max_value' => $maxValue,
        'days' => $data,
    ];
}

    /**
     * Get peak hour analysis data
     */
    protected function getPeakHourAnalysis(int $storeId, $date, int $factor): array
    {
        $hourlyData = Order::where('store_id', $storeId)
            ->whereDate('created_at', $date)
            ->where('status', 'delivered')
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as order_count, SUM(total_amount) as total_sales')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        $hours = [];
        $maxOrders = 0;
        $maxSales = 0;

        foreach ($hourlyData as $data) {
            $hours[$data->hour] = [
                'order_count' => (int) $data->order_count,
                'total_sales' => CurrencyHelper::fromMinorUnits($data->total_sales, $factor)
            ];

            if ($data->order_count > $maxOrders) {
                $maxOrders = $data->order_count;
                $maxSales = $data->total_sales;
            }
        }

        // Fill in missing hours
        for ($i = 0; $i < 24; $i++) {
            if (!isset($hours[$i])) {
                $hours[$i] = [
                    'order_count' => 0,
                    'total_sales' => 0
                ];
            }
        }

        ksort($hours);

        return [
            'hourly_data' => array_values($hours),
            'peak_hour' => array_search(max(array_column($hours, 'order_count')), array_column($hours, 'order_count')),
            'peak_sales' => CurrencyHelper::fromMinorUnits($maxSales, $factor)
        ];
    }
protected function getPeakHoursSlots(int $storeId, Carbon $date): array
{
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
            ->whereDate('created_at', $date)
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
                'total_balance' => $balance ? CurrencyHelper::fromMinorUnits($balance->total_balance, $factor) : 0,
                'available_balance' => $balance ? CurrencyHelper::fromMinorUnits($balance->available_balance, $factor) : 0,
                'pending_balance' => $balance ? CurrencyHelper::fromMinorUnits($balance->pending_balance, $factor) : 0,
                'currency' => $currencyInfo['symbol']
            ],
            'commissions' => [
                'total_paid' => 0, // Would need to calculate from commission transactions
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
        $balance = Balance::where('balanceable_id', $vendor->id)
            ->where('balanceable_type', get_class($vendor))
            ->first();

        if (!$balance) {
            return [
                'total_balance' => 0,
                'available_for_withdrawal' => 0,
                'currency' => $currencyInfo['symbol']
            ];
        }

        return [
            'total_balance' => CurrencyHelper::fromMinorUnits($balance->total_balance, $factor),
            'available_for_withdrawal' => CurrencyHelper::fromMinorUnits($balance->available_balance, $factor),
            'currency' => $currencyInfo['symbol']
        ];
    }

    /**
     * Get recent transactions
     */
    protected function getRecentTransactions($vendor, array $currencyInfo): array
    {
        $factor = $currencyInfo['factor'];
        $transactions = Transaction::where(function($query) use ($vendor) {
                $query->where('store_id', $vendor->store_id)
                      ->orWhere(function($q) use ($vendor) {
                          $q->where('transactionable_type', 'store')
                            ->where('transactionable_id', $vendor->store_id);
                      });
            })
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return $transactions->map(function ($transaction) use ($factor) {
            return [
                'id' => $transaction->id,
                'type' => $transaction->type,
                'amount' => CurrencyHelper::fromMinorUnits($transaction->amount, $factor),
                'currency' => $transaction->currency,
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
