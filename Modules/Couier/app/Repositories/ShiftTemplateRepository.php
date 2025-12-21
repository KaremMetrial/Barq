<?php

namespace Modules\Couier\Repositories;

use App\Repositories\BaseRepository;
use Modules\Couier\Models\ShiftTemplate;

class ShiftTemplateRepository extends BaseRepository
{
    public function __construct(ShiftTemplate $model)
    {
        parent::__construct($model);
    }

    public function getAll(array $filters = [])
    {
        $query = $this->model->with(['days', 'store']);

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        if (isset($filters['store_id'])) {
            $query->where('store_id', $filters['store_id']);
        }
        if(auth('vendor')->check()){
            $query->where('store_id',auth('vendor')->user()->store_id);
        }
        if (isset($filters['is_flexible'])) {
            $query->where('is_flexible', $filters['is_flexible']);
        }
        if (isset($filters['courier_id'])) {
            $courierId = $filters['courier_id'];
            $query->whereHas('courierAssignments', function ($q) use ($courierId) {
                $q->where('courier_id', $courierId);
            });
        }
        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }

    public function getActiveTemplates(?int $storeId = null)
    {
        $query = $this->model->where('is_active', true)->with('days');

        if ($storeId) {
            $query->where('store_id', $storeId);
        } else {
            // If no store provided, only return general templates (null store_id)
            $query->whereNull('store_id');
        }

        return $query->get();
    }

    public function getActiveTemplatesForCourierStore(?int $storeId = null)
    {
        $query = $this->model->where('is_active', true)->with('days');

        if ($storeId) {
            // For couriers, prioritize their store's templates, fallback to general templates
            $query->where(function ($q) use ($storeId) {
                $q->where('store_id', $storeId)
                  ->orWhereNull('store_id');
            });
        } else {
            // If no store provided, only return general templates
            $query->whereNull('store_id');
        }

        return $query->get();
    }
}
