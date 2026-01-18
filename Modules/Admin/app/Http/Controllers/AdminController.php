<?php

namespace Modules\Admin\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Modules\User\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Modules\Admin\Services\AdminService;
use Modules\Withdrawal\Models\Withdrawal;
use App\Http\Resources\PaginationResource;
use Modules\Admin\Http\Requests\LoginRequest;
use Modules\Admin\Http\Resources\AdminResource;
use Modules\Order\Http\Resources\OrderResource;
use Modules\Admin\Http\Requests\CreateAdminRequest;
use Modules\Admin\Http\Requests\UpdateAdminRequest;
use Modules\Admin\Http\Requests\UpdatePasswordRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Modules\Withdrawal\Http\Resources\WithdrawalResource;

class AdminController extends Controller
{
    use ApiResponse, AuthorizesRequests;

    public function __construct(protected AdminService $adminService) {}

    /**
     * Display a listing of the admins.
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', \Modules\Admin\Models\Admin::class);
        $admins = $this->adminService->getAllAdmins();

        return $this->successResponse([
            "admins" => AdminResource::collection($admins),
        ], __("message.success"));
    }

    /**
     * Store a newly created admin.
     */
    public function store(CreateAdminRequest $request): JsonResponse
    {
        $this->authorize('create', \Modules\Admin\Models\Admin::class);
        $admin = $this->adminService->createAdmin($request->all());

        return $this->successResponse([
            "admin" => new AdminResource($admin),
        ], __("message.success"));
    }

    /**
     * Display the specified admin.
     */
    public function show(int $id): JsonResponse
    {
        $admin = $this->adminService->getAdminById($id);
        $this->authorize('view', $admin);

        return $this->successResponse([
            "admin" => new AdminResource($admin),
        ], __("message.success"));
    }

    /**
     * Update the specified admin.
     */
    public function update(UpdateAdminRequest $request, int $id): JsonResponse
    {
        $admin = $this->adminService->getAdminById($id);
        $this->authorize('update', $admin);
        $admin = $this->adminService->updateAdmin($id, $request->all());

        return $this->successResponse([
            "admin" => new AdminResource($admin),
        ], __("message.success"));
    }

    /**
     * Remove the specified admin from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $admin = $this->adminService->getAdminById($id);
        $this->authorize('delete', $admin);
        $this->adminService->deleteAdmin($id);

        return $this->successResponse(null, __("message.success"));
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $admin = $this->adminService->login($request->validated());
        return $this->successResponse([
            'admin' => new AdminResource($admin['admin']),
            'token' => $admin['token'],
            'role' => $admin['role'][0],
            'permissions' => $admin['permissions'],
        ], __('message.success'));
    }
    public function logout(Request $request): JsonResponse
    {
        $vendor = $this->adminService->logout($request);

        return $this->successResponse(null, __('message.success'));
    }
    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $vendor = $this->adminService->updatePassword($request->validated());
        return $this->successResponse(null, __('message.success'));
    }

    /**
     * Get the authenticated admin's profile.
     */
    public function profile(): JsonResponse
    {
        $admin = auth('sanctum')->user();
        return $this->successResponse([
            "admin" => new AdminResource($admin),
        ], __("message.success"));
    }

    /**
     * Get reports dashboard data.
     */
    public function reports(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'store_id', 'category_id', 'city', 'from_date', 'to_date']);

        // Get currency information based on user's country or default
        $currencyCode = config('settings.default_currency', 'USD');
        $currencyFactor = 100; // Default factor
        $countryId = null;

        // Try to get country_id from authenticated user's token
        if (auth('sanctum')->check()) {
            $user = auth('sanctum')->user();

            // Check if user has country_id in their token
            if ($user->currentAccessToken() && $user->currentAccessToken()->country_id) {
                $countryId = $user->currentAccessToken()->country_id;
            }

            // If no country_id in token, use default country id from settings
            if (!$countryId) {
                $countryId = config('settings.default_country', 1);
            }
        } else {
            // If no authenticated user, use default country
            $countryId = config('settings.default_country', 1);
        }

        // Get currency info from country
        if ($countryId) {
            $country = \Modules\Country\Models\Country::find($countryId);
            if ($country) {
                $currencyCode = $country->currency_name ?? config('settings.default_currency', 'USD');
                $currencyFactor = $country->currency_factor ?? 100;
            }
        }

        $query = \Modules\Order\Models\Order::query();

        // Filter orders by country if countryId is available
        if ($countryId) {
            $query->whereHas('store.address.zone.city.governorate', function ($q) use ($countryId) {
                $q->where('country_id', $countryId);
            });
        }

        // Apply filters
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['store_id'])) {
            $query->where('store_id', $filters['store_id']);
        }
        if (!empty($filters['category_id'])) {
            $query->whereHas('orderItems.product', function ($q) use ($filters) {
                $q->where('category_id', $filters['category_id']);
            });
        }
        if (!empty($filters['city'])) {
            $query->whereHas('store.address', function ($q) use ($filters) {
                $q->where('city', $filters['city']);
            });
        }
        if (!empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }
        if (!empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        // Metrics
        $totalSales = (int) $query->sum('total_amount');
        $totalOrders = $query->count();
        $avgOrderValue = $totalOrders > 0 ? round($totalSales / $totalOrders, 2) : 0;

        // Calculate previous period metrics for percentage comparison
        $previousPeriodQuery = \Modules\Order\Models\Order::query();

        // Apply same filters for previous period
        if (!empty($filters['status'])) {
            $previousPeriodQuery->where('status', $filters['status']);
        }
        if (!empty($filters['store_id'])) {
            $previousPeriodQuery->where('store_id', $filters['store_id']);
        }
        if (!empty($filters['category_id'])) {
            $previousPeriodQuery->whereHas('orderItems.product', function ($q) use ($filters) {
                $q->where('category_id', $filters['category_id']);
            });
        }
        if (!empty($filters['city'])) {
            $previousPeriodQuery->whereHas('store.address', function ($q) use ($filters) {
                $q->where('city', $filters['city']);
            });
        }

        // Calculate date range for previous period
        $previousFromDate = null;
        $previousToDate = null;

        if (!empty($filters['from_date']) && !empty($filters['to_date'])) {
            $dateDiff = strtotime($filters['to_date']) - strtotime($filters['from_date']);
            $previousToDate = date('Y-m-d', strtotime($filters['from_date']) - 1);
            $previousFromDate = date('Y-m-d', strtotime($previousToDate) - $dateDiff);
        } elseif (!empty($filters['from_date'])) {
            $previousToDate = date('Y-m-d', strtotime($filters['from_date']) - 1);
            $previousFromDate = date('Y-m-d', strtotime($previousToDate) - 30); // Default to 30 days
        } elseif (!empty($filters['to_date'])) {
            $previousToDate = date('Y-m-d', strtotime($filters['to_date']) - 1);
            $previousFromDate = date('Y-m-d', strtotime($previousToDate) - 30); // Default to 30 days
        } else {
            // Default to previous 30 days
            $previousToDate = date('Y-m-d', strtotime('-1 day'));
            $previousFromDate = date('Y-m-d', strtotime('-31 days'));
        }

        $previousPeriodQuery->whereDate('created_at', '>=', $previousFromDate)
                           ->whereDate('created_at', '<=', $previousToDate);

        $previousTotalSales = (float) $previousPeriodQuery->sum('total_amount');
        $previousTotalOrders = $previousPeriodQuery->count();
        $previousAvgOrderValue = $previousTotalOrders > 0 ? round($previousTotalSales / $previousTotalOrders, 2) : 0;

        // Delivery Metrics
        $deliveredOrders = $query->clone()->where('status', 'delivered')->count();
        $cancelledOrders = $query->clone()->where('status', 'cancelled')->count();
        $returnedOrders = $query->clone()->where('status', 'returned')->count();
        $pendingOrders = $query->clone()->where('status', 'pending')->count();

        // Average delivery time (assuming there's a delivered_at field)
        $avgDeliveryTime = 0;
        $deliveryQuery = \Modules\Order\Models\Order::whereNotNull('delivered_at')
            ->when(!empty($filters['from_date']), fn($q) => $q->whereDate('created_at', '>=', $filters['from_date']))
            ->when(!empty($filters['to_date']), fn($q) => $q->whereDate('created_at', '<=', $filters['to_date']));

        // Apply country filter to delivery time queries
        if ($countryId) {
            $deliveryQuery->whereHas('store.address.zone.city.governorate', function ($q) use ($countryId) {
                $q->where('country_id', $countryId);
            });
        }

        $deliveredOrdersWithTime = $deliveryQuery->count();

        if ($deliveredOrdersWithTime > 0) {
            $avgDeliveryTime = round($deliveryQuery->avg(DB::raw('TIMESTAMPDIFF(MINUTE, created_at, delivered_at)')), 0);
        }

        // Commission Earned: Sum of commissions based on store settings
        $commissionQuery = \Modules\Order\Models\Order::query();
        if (!empty($filters['status'])) {
            $commissionQuery->where('status', $filters['status']);
        }
        if (!empty($filters['store_id'])) {
            $commissionQuery->where('store_id', $filters['store_id']);
        }
        if (!empty($filters['category_id'])) {
            $commissionQuery->whereHas('orderItems.product', function ($q) use ($filters) {
                $q->where('category_id', $filters['category_id']);
            });
        }
        if (!empty($filters['city'])) {
            $commissionQuery->whereHas('store.address', function ($q) use ($filters) {
                $q->where('city', $filters['city']);
            });
        }
        if (!empty($filters['from_date'])) {
            $commissionQuery->whereDate('created_at', '>=', $filters['from_date']);
        }
        if (!empty($filters['to_date'])) {
            $commissionQuery->whereDate('created_at', '<=', $filters['to_date']);
        }
        $commissionEarned = $commissionQuery->join('stores', 'orders.store_id', '=', 'stores.id')
            ->selectRaw('SUM(CASE
                WHEN stores.commission_type = "percentage" THEN orders.total_amount * (stores.commission_amount / 100)
                WHEN stores.commission_type = "fixed" THEN stores.commission_amount
                ELSE 0 END) as commission')
            ->value('commission') ?? 0;
        // Refunds: Sum of transactions with type 'refund' or orders with payment_status unpaid/partially_paid
        $refunds = \App\Models\Transaction::where('type', 'refund')
            ->when(!empty($filters['from_date']), fn($q) => $q->whereDate('created_at', '>=', $filters['from_date']))
            ->when(!empty($filters['to_date']), fn($q) => $q->whereDate('created_at', '<=', $filters['to_date']))
            ->sum('amount');
        $refundOrderQuery = \Modules\Order\Models\Order::query();
        if (!empty($filters['status'])) {
            $refundOrderQuery->where('status', $filters['status']);
        }
        if (!empty($filters['store_id'])) {
            $refundOrderQuery->where('store_id', $filters['store_id']);
        }
        if (!empty($filters['category_id'])) {
            $refundOrderQuery->whereHas('orderItems.product', function ($q) use ($filters) {
                $q->where('category_id', $filters['category_id']);
            });
        }
        if (!empty($filters['city'])) {
            $refundOrderQuery->whereHas('store.address', function ($q) use ($filters) {
                $q->where('city', $filters['city']);
            });
        }
        if (!empty($filters['from_date'])) {
            $refundOrderQuery->whereDate('created_at', '>=', $filters['from_date']);
        }
        if (!empty($filters['to_date'])) {
            $refundOrderQuery->whereDate('created_at', '<=', $filters['to_date']);
        }
        $refunds += $refundOrderQuery->whereIn('payment_status', [\App\Enums\PaymentStatusEnum::UNPAID, \App\Enums\PaymentStatusEnum::PARTIALLY_PAID])
            ->sum('total_amount');

        // Calculate previous period refunds for percentage comparison
        $previousRefunds = \App\Models\Transaction::where('type', 'refund')
            ->whereDate('created_at', '>=', $previousFromDate)
            ->whereDate('created_at', '<=', $previousToDate)
            ->sum('amount');

        $previousRefundOrderQuery = \Modules\Order\Models\Order::query();
        if (!empty($filters['status'])) {
            $previousRefundOrderQuery->where('status', $filters['status']);
        }
        if (!empty($filters['store_id'])) {
            $previousRefundOrderQuery->where('store_id', $filters['store_id']);
        }
        if (!empty($filters['category_id'])) {
            $previousRefundOrderQuery->whereHas('orderItems.product', function ($q) use ($filters) {
                $q->where('category_id', $filters['category_id']);
            });
        }
        if (!empty($filters['city'])) {
            $previousRefundOrderQuery->whereHas('store.address', function ($q) use ($filters) {
                $q->where('city', $filters['city']);
            });
        }
        $previousRefundOrderQuery->whereDate('created_at', '>=', $previousFromDate)
                               ->whereDate('created_at', '<=', $previousToDate);
        $previousRefunds += $previousRefundOrderQuery->whereIn('payment_status', [\App\Enums\PaymentStatusEnum::UNPAID, \App\Enums\PaymentStatusEnum::PARTIALLY_PAID])
            ->sum('total_amount');

        // Calculate previous period commission for percentage comparison
        $previousCommissionQuery = \Modules\Order\Models\Order::query();
        if (!empty($filters['status'])) {
            $previousCommissionQuery->where('status', $filters['status']);
        }
        if (!empty($filters['store_id'])) {
            $previousCommissionQuery->where('store_id', $filters['store_id']);
        }
        if (!empty($filters['category_id'])) {
            $previousCommissionQuery->whereHas('orderItems.product', function ($q) use ($filters) {
                $q->where('category_id', $filters['category_id']);
            });
        }
        if (!empty($filters['city'])) {
            $previousCommissionQuery->whereHas('store.address', function ($q) use ($filters) {
                $q->where('city', $filters['city']);
            });
        }
        $previousCommissionQuery->whereDate('orders.created_at', '>=', $previousFromDate)
                              ->whereDate('orders.created_at', '<=', $previousToDate);
        $previousCommissionEarned = $previousCommissionQuery->join('stores', 'orders.store_id', '=', 'stores.id')
            ->selectRaw('SUM(CASE
                WHEN stores.commission_type = "percentage" THEN orders.total_amount * (stores.commission_amount / 100)
                WHEN stores.commission_type = "fixed" THEN stores.commission_amount
                ELSE 0 END) as commission')
            ->value('commission') ?? 0;

        // Charts
        $salesQuery = clone $query;
        $salesOverTime = $salesQuery->where('status', 'delivered')
            ->selectRaw('DATE(orders.created_at) as date, SUM(orders.total_amount) as value')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn($item) => ['date' => $item->date, 'value' => (float) $item->value]);

        $orderQuery = clone $query;
        $ordersOverTime = $orderQuery->selectRaw('DATE(orders.created_at) as date, SUM(orders.total_amount) as value')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn($item) => ['date' => $item->date, 'value' => (float) $item->value]);

        $userQuery = User::query();
        $userOverTime = $userQuery->selectRaw('DATE(users.created_at) as date, COUNT(*) as value')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn($item) => ['date' => $item->date, 'value' => (int) $item->value]);

        // Top stores by revenue (existing)
        $topStoresQuery = clone $query;
        $topStoresByRevenue = $topStoresQuery->join('stores', 'orders.store_id', '=', 'stores.id')
            ->join('store_translations', 'stores.id', '=', 'store_translations.store_id')
            ->where('store_translations.locale', app()->getLocale())
            ->selectRaw('stores.id, store_translations.name, SUM(orders.total_amount) as revenue')
            ->groupBy('stores.id', 'store_translations.name')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get()
            ->map(fn($item) => ['id' => $item->id, 'name' => $item->name, 'revenue' => (float) $item->revenue]);

        // Top stores by order count (new for UI)
        $topStoresByOrdersQuery = clone $query;
        $topStoresByOrders = $topStoresByOrdersQuery->join('stores', 'orders.store_id', '=', 'stores.id')
            ->join('store_translations', 'stores.id', '=', 'store_translations.store_id')
            ->where('store_translations.locale', app()->getLocale())
            ->selectRaw('stores.id, store_translations.name, COUNT(orders.id) as order_count')
            ->groupBy('stores.id', 'store_translations.name')
            ->orderByDesc('order_count')
            ->limit(5)
            ->get()
            ->map(fn($item) => ['id' => $item->id, 'name' => $item->name, 'order_count' => (int) $item->order_count]);

        // Order status distribution
        $statusDistributionQuery = \Modules\Order\Models\Order::select('status')
            ->selectRaw('COUNT(*) as count')
            ->when(!empty($filters['status']), fn($q) => $q->where('status', $filters['status']))
            ->when(!empty($filters['store_id']), fn($q) => $q->where('store_id', $filters['store_id']))
            ->when(!empty($filters['category_id']), fn($q) => $q->whereHas('orderItems.product', function ($q) use ($filters) {
                $q->where('category_id', $filters['category_id']);
            }))
            ->when(!empty($filters['city']), fn($q) => $q->whereHas('store.address', function ($q) use ($filters) {
                $q->where('city', $filters['city']);
            }))
            ->when(!empty($filters['from_date']), fn($q) => $q->whereDate('created_at', '>=', $filters['from_date']))
            ->when(!empty($filters['to_date']), fn($q) => $q->whereDate('created_at', '<=', $filters['to_date']));

        // Apply country filter
        if ($countryId) {
            $statusDistributionQuery->whereHas('store.address.zone.city.governorate', function ($q) use ($countryId) {
                $q->where('country_id', $countryId);
            });
        }

        $statusDistribution = $statusDistributionQuery->groupBy('status')
            ->get()
            ->map(fn($item) => ['status' => $item->status, 'count' => (int) $item->count]);

        // Customer Analytics
        $customerQuery = \Modules\User\Models\User::query();

        // Apply customer filters if needed
        if (!empty($filters['from_date'])) {
            $customerQuery->whereDate('created_at', '>=', $filters['from_date']);
        }
        if (!empty($filters['to_date'])) {
            $customerQuery->whereDate('created_at', '<=', $filters['to_date']);
        }

        $totalCustomers = $customerQuery->count();

        // New vs Returning Customers
        $newCustomers = $customerQuery->clone()
            ->where('created_at', '>=', now()->subDays(30)) // New customers (last 30 days)
            ->count();

        $returningCustomers = $totalCustomers - $newCustomers;

        // Gender Distribution - Remove since gender column doesn't exist in users table
        $genderDistribution = null;

        // Top Cities by Customer Count
        $topCities = $customerQuery->clone()
            ->join('addresses', 'users.id', '=', 'addresses.addressable_id')
            ->where('addresses.addressable_type', 'user')
            ->join('zones', 'addresses.zone_id', '=', 'zones.id')
            ->join('cities', 'zones.city_id', '=', 'cities.id')
            ->join('city_translations', 'cities.id', '=', 'city_translations.city_id')
            ->where('city_translations.locale', app()->getLocale())
            ->selectRaw('city_translations.name as city_name, COUNT(users.id) as customer_count')
            ->groupBy('cities.id', 'city_translations.name')
            ->orderByDesc('customer_count')
            ->limit(10)
            ->get()
            ->map(fn($item) => ['city_name' => $item->city_name, 'customer_count' => (int) $item->customer_count]);

        // Calculate Performance Metrics for Radar Chart
        $ordersPerformance = $totalOrders > 0 ? round(($deliveredOrders / $totalOrders) * 100, 0) : 0;

        // Calculate average rating (using store ratings as proxy)
        $averageRating = \Modules\Review\Models\Review::avg('rating') ?? 0;
        $ratingPerformance = round(($averageRating / 5) * 100, 0); // Convert 1-5 scale to 0-100

        // Calculate profit performance (using commission as proxy for profit)
        $profitPerformance = $totalSales > 0 ? round(($commissionEarned / $totalSales) * 100, 0) : 0;

        // Calculate speed performance (inverse of delivery time, normalized)
        $maxAcceptableTime = 120; // 2 hours max acceptable delivery time in minutes
        $speedPerformance = $avgDeliveryTime > 0 ? max(0, round(100 - (($avgDeliveryTime / $maxAcceptableTime) * 100), 0)) : 100;

        // Calculate quality performance (based on refund rate)
        $refundRate = $totalSales > 0 ? ($refunds / $totalSales) : 0;
        $qualityPerformance = round(100 - ($refundRate * 100), 0);

        // Ensure all values are within 0-100 range
        $ordersPerformance = max(0, min(100, $ordersPerformance));
        $ratingPerformance = max(0, min(100, $ratingPerformance));
        $profitPerformance = max(0, min(100, $profitPerformance));
        $speedPerformance = max(0, min(100, $speedPerformance));
        $qualityPerformance = max(0, min(100, $qualityPerformance));

        // Average delivery time trend (weekly)
        $deliveryTimeTrendQuery = \Modules\Order\Models\Order::whereNotNull('delivered_at')
            ->when(!empty($filters['from_date']), fn($q) => $q->whereDate('created_at', '>=', $filters['from_date']))
            ->when(!empty($filters['to_date']), fn($q) => $q->whereDate('created_at', '<=', $filters['to_date']));

        // Apply country filter
        if ($countryId) {
            $deliveryTimeTrendQuery->whereHas('store.address.zone.city.governorate', function ($q) use ($countryId) {
                $q->where('country_id', $countryId);
            });
        }

        $deliveryTimeTrend = $deliveryTimeTrendQuery->selectRaw('WEEK(created_at) as week, AVG(TIMESTAMPDIFF(MINUTE, created_at, delivered_at)) as avg_time')
            ->groupBy('week')
            ->orderBy('week')
            ->limit(4)
            ->get()
            ->map(fn($item) => ['week' => 'Week ' . $item->week, 'avg_time' => (int) $item->avg_time]);

        $paymentMethodsQuery = clone $query;
        $paymentMethods = $paymentMethodsQuery->join('payment_methods', 'orders.payment_method_id', '=', 'payment_methods.id')
            ->selectRaw('payment_methods.id, payment_methods.name, COUNT(*) as count')
            ->groupBy('payment_methods.id', 'payment_methods.name')
            ->get()
            ->map(fn($item) => ['method' => $item->name, 'count' => $item->count]);

        // Recent Orders with courier information
        $recentwithdrawels = Withdrawal::with(['withdrawable'])
            ->latest()
            ->paginate(10);
        $recentOrders = $query->with(['store', 'user', 'courier'])
            // ->select('id', 'order_number', 'total_amount', 'status', 'created_at', 'store_id', 'user_id')
            ->latest()
            ->paginate(10);

        // Calculate percentage changes and determine increment/decrement
        $totalSalesChange = $totalSales - $previousTotalSales;
        $totalSalesPercentage = $previousTotalSales > 0 ? round(($totalSalesChange / $previousTotalSales) * 100, 2) : ($totalSales > 0 ? 100 : 0);
        $totalSalesIncrement = $totalSalesChange > 0;

        $totalOrdersChange = $totalOrders - $previousTotalOrders;
        $totalOrdersPercentage = $previousTotalOrders > 0 ? round(($totalOrdersChange / $previousTotalOrders) * 100, 2) : ($totalOrders > 0 ? 100 : 0);
        $totalOrdersIncrement = $totalOrdersChange > 0;

        $avgOrderValueChange = $avgOrderValue - $previousAvgOrderValue;
        $avgOrderValuePercentage = $previousAvgOrderValue > 0 ? round(($avgOrderValueChange / $previousAvgOrderValue) * 100, 2) : ($avgOrderValue > 0 ? 100 : 0);
        $avgOrderValueIncrement = $avgOrderValueChange > 0;

        $commissionEarnedChange = $commissionEarned - $previousCommissionEarned;
        $commissionEarnedPercentage = $previousCommissionEarned > 0 ? round(($commissionEarnedChange / $previousCommissionEarned) * 100, 2) : ($commissionEarned > 0 ? 100 : 0);
        $commissionEarnedIncrement = $commissionEarnedChange > 0;

        $refundsChange = $refunds - $previousRefunds;
        $refundsPercentage = $previousRefunds > 0 ? round(($refundsChange / $previousRefunds) * 100, 2) : ($refunds > 0 ? 100 : 0);
        $refundsIncrement = $refundsChange > 0;



        return $this->successResponse([
            'currency_code' => $currencyCode,
            'currency_factor' => $currencyFactor,
            'order_widget' => [
                'totalOrders' => $totalOrders,
                'periodOrders' => $previousTotalOrders,
                'periodRate' => $totalOrdersPercentage,
                'periodTrend' => $totalOrdersIncrement ? 'increment' : 'decrement'
            ],
            'sales_widget' => [
                'totalSales' => $totalSales,
                'periodSales' => $previousTotalSales,
                'periodRate' => $totalSalesPercentage,
                'periodTrend' => $totalSalesIncrement ? 'increment' : 'decrement'
            ],
            'average_order_value_widget' => [
                'totalAverage' => $avgOrderValue,
                'periodAverage' => $previousAvgOrderValue,
                'periodRate' => $avgOrderValuePercentage,
                'periodTrend' => $avgOrderValueIncrement ? 'increment' : 'decrement'
            ],
            'commission_widget' => [
                'totalCommission' => round($commissionEarned, 2),
                'periodCommission' => round($previousCommissionEarned, 2),
                'periodRate' => $commissionEarnedPercentage,
                'periodTrend' => $commissionEarnedIncrement ? 'increment' : 'decrement'
            ],
            'refunds_widget' => [
                'totalRefunds' => round($refunds, 2),
                'periodRefunds' => round($previousRefunds, 2),
                'periodRate' => $refundsPercentage,
                'periodTrend' => $refundsIncrement ? 'increment' : 'decrement'
            ],
            'metrics' => [
                'delivered_orders' => $deliveredOrders,
                'cancelled_orders' => $cancelledOrders,
                'returned_orders' => $returnedOrders,
                'pending_orders' => $pendingOrders,
                'average_delivery_time' => $avgDeliveryTime,
            ],
            'charts' => [
                'sales_over_time' => $salesOverTime,
                'order_over_time' => $ordersOverTime,
                'user_over_time' => $userOverTime,
                'top_stores_by_revenue' => $topStoresByRevenue,
                'top_stores_by_orders' => $topStoresByOrders,
                'order_status_distribution' => $statusDistribution,
                'delivery_time_trend' => $deliveryTimeTrend,
                'payment_methods' => $paymentMethods,
            ],
            'customer_analytics' => [
                'total_customers' => $totalCustomers,
                'new_customers' => $newCustomers,
                'returning_customers' => $returningCustomers,
                'top_cities' => $topCities,
            ],
            'recent_withdrawels' => [
                'withdrawals' => WithdrawalResource::collection($recentwithdrawels),
                'pagination' => new PaginationResource($recentwithdrawels),
            ],
            'recent_orders' => [
                'orders' => WithdrawalResource::collection($recentOrders),
                'pagination' => new PaginationResource($recentOrders),
            ],

        ], __('message.success'));
    }
}
