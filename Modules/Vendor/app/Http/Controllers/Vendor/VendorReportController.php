<?php

namespace Modules\Vendor\Http\Controllers\Vendor;

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
            $currency = $this->getStoreCurrency($storeId);

            $reportData = [
                'operational_performance' => $this->getOperationalPerformance($storeId, $startDate, $endDate, $currency),
                'financial_information' => $this->getFinancialInformation($vendor, $currency),
                'wallet_data' => $this->getWalletData($vendor, $currency),
                'transactions' => $this->getRecentTransactions($vendor),
                'currency' => $currency
            ];

            return $this->successResponse($reportData, __('message.success'));
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get operational performance data
     */
    protected function getOperationalPerformance(int $storeId, $startDate, $endDate, string $currency): array
    {
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
        $weeklySalesChart = $weeklySales->map(function ($item) {
            return [
                'date' => $item->date,
                'total_sales' => (float) $item->total_sales,
                'order_count' => (int) $item->order_count,
                'day_name' => Carbon::parse($item->date)->translatedFormat('l')
            ];
        });

        return [
            'daily_metrics' => [
                'total_sales' => [
                    'value' => (float) $totalSalesToday,
                    'change_percent' => (float) $salesChangePercent,
                    'currency' => $currency
                ],
                'order_count' => [
                    'value' => (int) $orderCountToday,
                    'change_percent' => (float) $orderCountChangePercent
                ],
                'average_order_value' => [
                    'value' => (float) $avgOrderValueToday,
                    'change_percent' => (float) $avgOrderValueChangePercent,
                    'currency' => $currency
                ],
                'peak_hours' => [
                    'value' => $peakHourRange,
                    'change_percent' => (float) $peakHourChangePercent
                ]
            ],
            'weekly_sales_chart' => $weeklySalesChart->values()->all(),
            'peak_hour_analysis' => $this->getPeakHourAnalysis($storeId, $today)
        ];
    }

    /**
     * Get peak hour analysis data
     */
    protected function getPeakHourAnalysis(int $storeId, $date): array
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
                'total_sales' => (float) $data->total_sales
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
            'peak_sales' => (float) $maxSales
        ];
    }

    /**
     * Get financial information
     */
    protected function getFinancialInformation($vendor, string $currency): array
    {
        $balance = Balance::where('balanceable_id', $vendor->id)
            ->where('balanceable_type', get_class($vendor))
            ->first();

        return [
            'wallet_balance' => [
                'total_balance' => $balance ? (float) $balance->total_balance : 0,
                'available_balance' => $balance ? (float) $balance->available_balance : 0,
                'pending_balance' => $balance ? (float) $balance->pending_balance : 0,
                'currency' => $currency
            ],
            'commissions' => [
                'total_paid' => 0, // Would need to calculate from commission transactions
                'currency' => $currency
            ]
        ];
    }

    /**
     * Get wallet data
     */
    protected function getWalletData($vendor, string $currency): array
    {
        $balance = Balance::where('balanceable_id', $vendor->id)
            ->where('balanceable_type', get_class($vendor))
            ->first();

        if (!$balance) {
            return [
                'total_balance' => 0,
                'available_for_withdrawal' => 0,
                'currency' => $currency
            ];
        }

        return [
            'total_balance' => (float) $balance->total_balance,
            'available_for_withdrawal' => (float) $balance->available_balance,
            'currency' => $currency
        ];
    }

    /**
     * Get recent transactions
     */
    protected function getRecentTransactions($vendor): array
    {
        $transactions = Transaction::where('user_id', $vendor->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return $transactions->map(function ($transaction) {
            return [
                'id' => $transaction->id,
                'type' => $transaction->type,
                'amount' => (float) $transaction->amount,
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
    protected function getStoreCurrency(int $storeId): string
    {
        $store = \Modules\Store\Models\Store::with('address.zone.city.governorate.country')->find($storeId);

        return $store->address?->zone?->city?->governorate?->country?->currency_symbol ?? 'EGP';
    }
}
