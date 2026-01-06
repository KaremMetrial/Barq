<?php

namespace Modules\Admin\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Modules\Admin\Services\AdminService;
use Modules\Admin\Http\Requests\LoginRequest;
use Modules\Admin\Http\Resources\AdminResource;
use Modules\Admin\Http\Requests\CreateAdminRequest;
use Modules\Admin\Http\Requests\UpdateAdminRequest;
use Modules\Admin\Http\Requests\UpdatePasswordRequest;
use Modules\Order\Http\Resources\OrderResource;
use App\Http\Resources\PaginationResource;
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

        return $this->successResponse([
            "admin" => new AdminResource($admin),
        ], __("message.success"));
    }

    /**
     * Update the specified admin.
     */
    public function update(UpdateAdminRequest $request, int $id): JsonResponse
    {
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

        $query = \Modules\Order\Models\Order::query();

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
        $totalSales = (float) $query->sum('total_amount');
        $totalOrders = $query->count();
        $avgOrderValue = $totalOrders > 0 ? round($totalSales / $totalOrders, 2) : 0;

        // Delivery Metrics
        $deliveredOrders = $query->clone()->where('status', 'delivered')->count();
        $cancelledOrders = $query->clone()->where('status', 'cancelled')->count();
        $returnedOrders = $query->clone()->where('status', 'returned')->count();
        $pendingOrders = $query->clone()->where('status', 'pending')->count();

        // Average delivery time (assuming there's a delivered_at field)
        $avgDeliveryTime = 0;
        $deliveredOrdersWithTime = \Modules\Order\Models\Order::whereNotNull('delivered_at')
            ->when(!empty($filters['from_date']), fn($q) => $q->whereDate('created_at', '>=', $filters['from_date']))
            ->when(!empty($filters['to_date']), fn($q) => $q->whereDate('created_at', '<=', $filters['to_date']))
            ->count();

        if ($deliveredOrdersWithTime > 0) {
            $avgDeliveryTime = round(\Modules\Order\Models\Order::whereNotNull('delivered_at')
                ->when(!empty($filters['from_date']), fn($q) => $q->whereDate('created_at', '>=', $filters['from_date']))
                ->when(!empty($filters['to_date']), fn($q) => $q->whereDate('created_at', '<=', $filters['to_date']))
                ->avg(DB::raw('TIMESTAMPDIFF(MINUTE, created_at, delivered_at)')), 0);
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

        // Charts
        $salesQuery = clone $query;
        $salesOverTime = $salesQuery->selectRaw('DATE(orders.created_at) as date, SUM(orders.total_amount) as value')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn($item) => ['date' => $item->date, 'value' => (float) $item->value]);

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
        $statusDistribution = \Modules\Order\Models\Order::select('status')
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
            ->when(!empty($filters['to_date']), fn($q) => $q->whereDate('created_at', '<=', $filters['to_date']))
            ->groupBy('status')
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

        // Average delivery time trend (weekly)
        $deliveryTimeTrend = \Modules\Order\Models\Order::whereNotNull('delivered_at')
            ->when(!empty($filters['from_date']), fn($q) => $q->whereDate('created_at', '>=', $filters['from_date']))
            ->when(!empty($filters['to_date']), fn($q) => $q->whereDate('created_at', '<=', $filters['to_date']))
            ->selectRaw('WEEK(created_at) as week, AVG(TIMESTAMPDIFF(MINUTE, created_at, delivered_at)) as avg_time')
            ->groupBy('week')
            ->orderBy('week')
            ->limit(4)
            ->get()
            ->map(fn($item) => ['week' => 'Week ' . $item->week, 'avg_time' => (int) $item->avg_time]);

        $paymentMethodsQuery = clone $query;
        $paymentMethods = $paymentMethodsQuery->join('payment_methods', 'orders.payment_method_id', '=', 'payment_methods.id')
            ->selectRaw('payment_methods.id, COUNT(*) as count')
            ->groupBy('payment_methods.id')
            ->get()
            ->map(fn($item) => ['method' => $item->name, 'count' => $item->count]);

        // Recent Orders with courier information
        $recentOrders = $query->with(['store', 'user', 'courier'])
            // ->select('id', 'order_number', 'total_amount', 'status', 'created_at', 'store_id', 'user_id')
            ->latest()
            ->paginate(10);

        return $this->successResponse([
            'metrics' => [
                'total_sales' => $totalSales,
                'total_orders' => $totalOrders,
                'average_order_value' => $avgOrderValue,
                'delivered_orders' => $deliveredOrders,
                'cancelled_orders' => $cancelledOrders,
                'returned_orders' => $returnedOrders,
                'pending_orders' => $pendingOrders,
                'average_delivery_time' => $avgDeliveryTime,
                'commission_earned' => round($commissionEarned, 2),
                'refunds' => round($refunds, 2),
            ],
            'charts' => [
                'sales_over_time' => $salesOverTime,
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
            'recent_orders' => [
                'orders' => OrderResource::collection($recentOrders),
                'pagination' => new PaginationResource($recentOrders),
            ],
        ], __('message.success'));
    }
}
