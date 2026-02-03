<?php

namespace Modules\Store\Repositories;

use App\Enums\SectionTypeEnum;
use App\Enums\OrderStatus;
use Modules\Store\Models\Store;
use Modules\Section\Models\Section;
use App\Repositories\BaseRepository;
use Modules\PosTerminal\Models\PosTerminal;
use Modules\Store\Repositories\Contracts\StoreRepositoryInterface;
use Modules\Vendor\Models\Vendor;
use Illuminate\Support\Facades\DB;

class StoreRepository extends BaseRepository implements StoreRepositoryInterface
{
    public function __construct(Store $model)
    {
        parent::__construct($model);
    }
    public function getStoresWithCustomCommission()
    {
        return $this->model
            ->where('commission_amount', '>', 0) // Assuming default commission is 0
            ->orWhere('commission_type', '!=', \App\Enums\PlanTypeEnum::COMMISSION) // Assuming default type is COMMISSION
            ->count();
    }

    public function getTotalPendingCommission()
    {
        return \Modules\Order\Models\Order::join('stores', 'orders.store_id', '=', 'stores.id')
            ->where('orders.payment_status', '!=', 'paid')
            ->sum(DB::raw('CASE WHEN stores.commission_type = "commission" THEN orders.total_amount * (stores.commission_amount / 100) ELSE stores.commission_amount END'));
    }


    public function getTotalEarnedCommission()
    {
        return \Modules\Order\Models\Order::join('stores', 'orders.store_id', '=', 'stores.id')
            ->where('orders.payment_status', 'paid')
            ->sum(DB::raw('CASE WHEN stores.commission_type = "commission" THEN orders.total_amount * (stores.commission_amount / 100) ELSE stores.commission_amount END'));
    }


    public function getStoresWithCustomCommissionCount()
    {
        return $this->model
            ->where(function ($query) {
                $query->where('commission_amount', '>', 0)
                    ->orWhere('commission_type', '!=', \App\Enums\PlanTypeEnum::COMMISSION);
            })
            ->count();
    }

    public function getCommissionStores(array $filters = [])
    {
        $query = $this->model->with(['section.translations', 'owner']);

        // Apply filters if provided
        if (!empty($filters['search'])) {
            $query->whereTranslationLike('name', '%' . $filters['search'] . '%');
        }

        // Get commission data by joining with orders
        $query = $query->selectRaw('stores.*,
                          COALESCE(store_orders.total_orders, 0) as total_orders,
                          COALESCE(store_orders.total_earned_commission, 0) as total_earned_commission,
                          COALESCE(store_orders.total_pending_commission, 0) as total_pending_commission')
            ->leftJoin(DB::raw('(
                  SELECT
                      o.store_id,
                      COUNT(o.id) as total_orders,
                       SUM(CASE 
                           WHEN o.payment_status = "paid" THEN 
                               (CASE WHEN s.commission_type = "commission" THEN o.total_amount * (s.commission_amount / 100) ELSE s.commission_amount END)
                           ELSE 0 
                       END) as total_earned_commission,
                       SUM(CASE 
                           WHEN o.payment_status != "paid" THEN 
                               (CASE WHEN s.commission_type = "commission" THEN o.total_amount * (s.commission_amount / 100) ELSE s.commission_amount END)
                           ELSE 0 
                       END) as total_pending_commission
                  FROM orders o
                  JOIN stores s ON o.store_id = s.id
                  GROUP BY o.store_id
              ) as store_orders'), 'stores.id', '=', 'store_orders.store_id');

        $query->orderByRaw('COALESCE(store_orders.total_earned_commission, 0) DESC');

        return $query->paginate($filters['per_page'] ?? 15);
    }


    public function getHomeStores(array $relations = [], array $filters = [])
    {
        if (empty($filters['section_id']) || $filters['section_id'] == 0) {
            $firstSection = Section::where('type', '!=', 'delivery_company')->orderBy('sort_order', 'asc')->first();
            if ($firstSection) {
                $filters['section_id'] = $firstSection->id;
            }
        }

        // 1. Define the base query for IDs
        $baseIdQuery = $this->model->filter($filters)
            ->whereHas('products', function ($q) {
                $q->where('is_active', true);
            });

        // 2. Fetch IDs for each section efficiently
        $featuredIds = (clone $baseIdQuery)->whereIsFeatured(true)->latest()->limit(5)->pluck('id')->toArray();
        $topReviewsIds = (clone $baseIdQuery)->withCount('reviews')->withAvg('reviews', 'rating')->orderByDesc('reviews_count')->orderByDesc('reviews_avg_rating')->limit(10)->pluck('id')->toArray();
        $newStoreIds = (clone $baseIdQuery)->where('created_at', '>=', now()->subDays(3))->limit(5)->pluck('id')->toArray();

        // 3. Merge and fetch all unique stores with relations in ONE query
        $allIds = array_unique(array_merge($featuredIds, $topReviewsIds, $newStoreIds));

        $allStores = $this->model->with($relations)
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->whereIn('id', $allIds)
            ->get()
            ->keyBy('id');

        // 4. Map back to original arrays (maintaining order)
        $featured = collect($featuredIds)->map(fn($id) => $allStores[$id] ?? null)->filter()->values();
        $topReviews = collect($topReviewsIds)->map(fn($id) => $allStores[$id] ?? null)->filter()->values();
        $newStore = collect($newStoreIds)->map(fn($id) => $allStores[$id] ?? null)->filter()->values();

        $sectionType = null;
        $sectionLabel = null;

        if (!empty($filters['section_id'])) {
            $section = Section::find($filters['section_id']);
            if ($section && $section->type) {
                $sectionName = $section->name;
                $sectionType = $section->type->value;
                $sectionLabel = SectionTypeEnum::label($section->type->value);
            }
        }

        return [
            'topReviews' => [
                'stores' => $topReviews,
                'label' => __(
                    'enums.section_labels.top_reviews',
                    ['section' => $sectionName]
                )
            ],
            'featured' => [
                'stores' => $featured,
                'label' => __(
                    'enums.section_labels.featured',
                    ['section' => $sectionName]
                )
            ],
            'newStores' => [
                'stores' => $newStore,
                'label' => __(
                    'enums.section_labels.new_stores',
                    ['section' => $sectionName]
                )
            ],
            'section_type' => $sectionType,
            'section_label' => $sectionLabel,
        ];
    }
    public function stats()
    {
        $vendorCount = Vendor::count();
        $storeCount = Store::where('type', '!=', 'delivery')->count();
        $posCount = PosTerminal::count();
        return [
            'vendorCount' => $vendorCount,
            'storeCount' => $storeCount,
            'posCount' => $posCount,
        ];
    }

    public function deliveryStore()
    {
        return $this->model->where('type', 'delivery')->with(['section', 'couriers'])->withCount(['couriers', 'orders', 'orders as successful_orders_count' => function ($query) {
            $query->where('status', OrderStatus::DELIVERED);
        }])->paginate(10);
    }

    public function getBranches(int $storeId)
    {
        $store = $this->model->find($storeId);
        if (!$store) {
            return $this->model->whereRaw('0 = 1')->paginate(10);
        }
        $parentId = $store->parent_id ?? $storeId;
        return $this->model->where('parent_id', $parentId)->paginate(10);
    }
}
