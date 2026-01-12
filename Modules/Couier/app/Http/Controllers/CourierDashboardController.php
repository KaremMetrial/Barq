<?php

namespace Modules\Couier\Http\Controllers;

use App\Traits\ApiResponse;
use App\Helpers\CurrencyHelper;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\Couier\Models\CourierOrderAssignment;

class CourierDashboardController extends Controller
{
    use ApiResponse, AuthorizesRequests;

    /**
     * Get courier dashboard with balance, stats, and transaction history
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'nullable|in:day,week,month'
        ]);

        $courierId = auth('sanctum')->id();
        $period = $request->get('period', 'week') ?? 'week';

        try {
            $courier = auth('sanctum')->user();

            // Get current balance
            $balance = $courier->balance?->current_balance ?? 0;

            // Get currency information
            $currencyInfo = CurrencyHelper::getCurrencyInfoFromStore($courier->store);

            // Calculate period date ranges
            $periodRanges = $this->getPeriodRanges($period);

            // Get current period stats
            $currentStats = $this->getCourierStats($courierId, $periodRanges['current']['start'], $periodRanges['current']['end']);

            // Get previous period stats for comparison
            $previousStats = $this->getCourierStats($courierId, $periodRanges['previous']['start'], $periodRanges['previous']['end']);

            // Calculate percentage changes
            $stats = $this->calculateStatsWithChanges($currentStats, $previousStats);

            // Get transaction history
            $transactionHistory = $this->getTransactionHistory($courierId, $periodRanges['current']['start'], $periodRanges['current']['end']);

            return $this->successResponse([
                'current_balance' => $balance,
                'currency_factor' => $currencyInfo['currency_factor'],
                'currency_code' => $currencyInfo['currency_code'],
                'period' => $period,
                'stats' => $stats,
                'transaction_history' => $transactionHistory,
            ], __('Dashboard data retrieved successfully'));

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    private function getPeriodRanges(string $period): array
    {
        $now = now();

        switch ($period) {
            case 'day':
                return [
                    'current' => [
                        'start' => today(),
                        'end' => now(),
                    ],
                    'previous' => [
                        'start' => today()->subDay(),
                        'end' => today()->subDay()->endOfDay(),
                    ],
                ];

            case 'week':
                return [
                    'current' => [
                        'start' => $now->copy()->startOfWeek(),
                        'end' => $now,
                    ],
                    'previous' => [
                        'start' => $now->copy()->subWeek()->startOfWeek(),
                        'end' => $now->copy()->subWeek()->endOfWeek(),
                    ],
                ];

            case 'month':
                return [
                    'current' => [
                        'start' => $now->copy()->startOfMonth(),
                        'end' => $now,
                    ],
                    'previous' => [
                        'start' => $now->copy()->subMonth()->startOfMonth(),
                        'end' => $now->copy()->subMonth()->endOfMonth(),
                    ],
                ];

            default:
                return $this->getPeriodRanges('week');
        }
    }

    private function getCourierStats(int $courierId, $startDate, $endDate): array
    {
        $query = CourierOrderAssignment::where('courier_id', $courierId)
            ->whereBetween('completed_at', [$startDate, $endDate]);

        return [
            'completed_orders' => (clone $query)->where('status', 'delivered')->count(),
            'cancelled_orders' => (clone $query)->where('status', 'failed')->count(),
            'total_earnings' => (clone $query)->where('status', 'delivered')->sum('actual_earning'),
        ];
    }

    private function calculateStatsWithChanges(array $current, array $previous): array
    {
        $calculateChange = function ($current, $previous) {
            if ($previous == 0) {
                return $current > 0 ? 100 : 0;
            }
            return round((($current - $previous) / $previous) * 100, 2);
        };

        return [
            'completed_orders' => [
                'count' => $current['completed_orders'],
                'change_percentage' => $calculateChange($current['completed_orders'], $previous['completed_orders']),
            ],
            'cancelled_orders' => [
                'count' => $current['cancelled_orders'],
                'change_percentage' => $calculateChange($current['cancelled_orders'], $previous['cancelled_orders']),
            ],
            'total_earnings' => [
                'amount' => (int) $current['total_earnings'],
                'change_percentage' => $calculateChange($current['total_earnings'], $previous['total_earnings']),
            ],
        ];
    }

    private function getTransactionHistory(int $courierId, $startDate, $endDate): array
    {
        $transactions = CourierOrderAssignment::where('courier_id', $courierId)
            ->whereBetween('completed_at', [$startDate, $endDate])
            ->whereIn('status', ['delivered', 'failed'])
            ->with(['order'])
            ->orderBy('completed_at', 'desc')
            ->take(50) // Limit to last 50 transactions
            ->get()
            ->groupBy(function ($assignment) {
                return $assignment->completed_at->format('Y-m-d');
            });

        foreach ($transactions as $date => $dayTransactions) {
            $dateLabel = $this->getDateLabel($date);

            $transactionList = $dayTransactions->map(function ($assignment) {
                $type = $assignment->status === 'delivered' ? 'collection' : 'payment';
                $amount = (int) $assignment->actual_earning;
                $description = $assignment->status === 'delivered'
                    ? "تحصيل - طلب #" . $assignment->order->id
                    : "مدفوعات مطعم - طلب #" . $assignment->order->id;

                return [
                    'type' => $type,
                    'amount' => $amount,
                    'description' => $description,
                    'time' => $assignment->completed_at->format('H:i'),
                    'order_id' => $assignment->order->id,
                ];
            });

            $history = [
                'date' => $date,
                'date_label' => $dateLabel,
                'transactions' => $transactionList,
            ];
        }

        return $history ?? null;
    }

    private function getDateLabel(string $date): string
    {
        $dateObj = \Carbon\Carbon::parse($date);
        $today = today();

        if ($dateObj->isSameDay($today)) {
            return 'اليوم';
        } elseif ($dateObj->isSameDay($today->copy()->subDay())) {
            return 'الأمس';
        } else {
            return $dateObj->locale('ar')->isoFormat('dddd, D MMMM');
        }
    }

    /**
     * Get courier earnings details with period summary and daily breakdown
     */
    public function earningsDetails(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'nullable|in:day,week,month'
        ]);

        $courierId = auth('sanctum')->id();
        $period = $request->get('period', 'week') ?? 'week';

        try {
            // Get period date ranges
            $periodRanges = $this->getPeriodRanges($period);

            // Get assignments for the period
            $assignments = CourierOrderAssignment::where('courier_id', $courierId)
                ->whereBetween('completed_at', [$periodRanges['current']['start'], $periodRanges['current']['end']])
                ->where('status', 'delivered')
                ->with(['order'])
                ->orderBy('completed_at', 'desc')
                ->get();

            // Calculate period summary
            $periodSummary = $this->calculatePeriodSummary($assignments, $periodRanges);

            // Calculate daily breakdown
            $dailyBreakdown = $this->calculateDailyBreakdown($assignments);

            return $this->successResponse([
                'period' => $period,
                'period_summary' => $periodSummary,
                'daily_breakdown' => $dailyBreakdown,
                'currency_factor' => auth('sanctum')->user()->store->getCurrencyFactor(),
                'currency_code' => auth('sanctum')->user()->store->getCurrencyCode()
            ], __('Earnings details retrieved successfully'));

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    private function calculatePeriodSummary($assignments, array $periodRanges): array
    {
        // Calculate total hours worked
        $totalHours = 0;
        foreach ($assignments as $assignment) {
            if ($assignment->started_at && $assignment->completed_at) {
                $hours = $assignment->started_at->diffInMinutes($assignment->completed_at) / 60;
                $totalHours += $hours;
            }
        }

        // Total earnings
        $totalEarnings = $assignments->sum('actual_earning');

        // Average per hour
        $averagePerHour = $totalHours > 0 ? round($totalEarnings / $totalHours, 2) : 0;

        // Format hours (Arabic: hours and days)
        $days = floor($totalHours / 24);
        $hours = floor($totalHours % 24);
        $deliveryHours = $hours . ' س ' . $days . ' د';

        return [
            'delivery_hours' => $deliveryHours,
            'average_per_hour' => $averagePerHour,
            'date_range' => [
                'start' => $periodRanges['current']['start']->format('Y-m-d'),
                'end' => $periodRanges['current']['end']->format('Y-m-d'),
                'start_formatted' => $periodRanges['current']['start']->locale('ar')->isoFormat('D MMMM'),
                'end_formatted' => $periodRanges['current']['end']->locale('ar')->isoFormat('D MMMM'),
            ],
        ];
    }

    private function calculateDailyBreakdown($assignments): array
    {
        $dailyData = [];

        // Group assignments by date
        $groupedByDate = $assignments->groupBy(function ($assignment) {
            return $assignment->completed_at->format('Y-m-d');
        });

        foreach ($groupedByDate as $date => $dayAssignments) {
            $dailyEarnings = $dayAssignments->sum('actual_earning');
            $ordersDelivered = $dayAssignments->count();

            // Get transactions for this day
            $transactions = $dayAssignments->map(function ($assignment) {
                return [
                    'type' => 'collection',
                    'amount' => (int) $assignment->actual_earning,
                    'description' => "تحصيل - طلب #" . $assignment->order->id,
                    'time' => $assignment->completed_at->format('H:i'),
                    'order_id' => $assignment->order->id,
                ];
            });

            // Add some payment transactions (simulated based on order data)
            $payments = $dayAssignments->map(function ($assignment) {
                // Simulate restaurant payments (typically negative)
                $paymentAmount = -($assignment->actual_earning * 0.3); // 30% goes to restaurant
                return [
                    'type' => 'payment',
                    'amount' => round($paymentAmount, 2),
                    'description' => "مدفوعات مطعم - طلب #" . $assignment->order->id,
                    'time' => $assignment->completed_at->addMinutes(5)->format('H:i'),
                    'order_id' => $assignment->order->id,
                ];
            });

            $allTransactions = collect($transactions)->concat($payments)->sortBy('time');

            $dailyData[] = [
                'date' => $date,
                'day_name' => \Carbon\Carbon::parse($date)->locale('ar')->isoFormat('dddd, D MMMM'),
                'earnings' => round($dailyEarnings, 2),
                'orders_delivered' => $ordersDelivered,
                'transactions' => $allTransactions->values()->all(),
            ];
        }

        // Sort by date descending (most recent first)
        return collect($dailyData)->sortByDesc('date')->values()->all();
    }
}
