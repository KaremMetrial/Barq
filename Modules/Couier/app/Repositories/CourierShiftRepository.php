<?php

namespace Modules\Couier\Repositories;

use App\Repositories\BaseRepository;
use Modules\Couier\Models\CouierShift;

class CourierShiftRepository extends BaseRepository
{
    public function __construct(CouierShift $model)
    {
        parent::__construct($model);
    }

    public function findActiveShift($courierId)
    {
        return $this->model->where('couier_id', $courierId)
            ->where('is_open', true)
            ->with('shiftTemplate')
            ->first();
    }

    public function getCourierHistory($courierId, array $filters = [])
    {
        $query = $this->model->where('couier_id', $courierId)->with('shiftTemplate');

        if (isset($filters['from_date'])) {
            $query->whereDate('start_time', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->whereDate('start_time', '<=', $filters['to_date']);
        }

        return $query->latest('start_time')->paginate($filters['per_page'] ?? 15);
    }

    public function getAll(array $filters = [])
    {
        $query = $this->model->with(['couier', 'shiftTemplate']);

        if (isset($filters['couier_id'])) {
            $query->where('couier_id', $filters['couier_id']);
        }

        if (isset($filters['is_open'])) {
            $query->where('is_open', $filters['is_open']);
        }

        if (isset($filters['from_date'])) {
            $query->whereDate('start_time', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->whereDate('start_time', '<=', $filters['to_date']);
        }

        return $query->latest('start_time')->paginate($filters['per_page'] ?? 15);
    }

    public function getCourierStats($courierId, array $filters = [])
    {
        $query = $this->model->where('couier_id', $courierId)
            ->where('is_open', false);

        if (isset($filters['from_date'])) {
            $query->whereDate('start_time', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->whereDate('start_time', '<=', $filters['to_date']);
        }

        return $query->get();
    }

    public function findScheduledShift($courierId, $scheduledDate)
    {
        return $this->model->where('couier_id', $courierId)
            ->whereDate('start_time', $scheduledDate)
            ->where('is_open', false)
            ->whereNull('end_time') // Not completed shifts, but scheduled
            ->first();
    }
}
