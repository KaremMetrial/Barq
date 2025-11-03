<?php

namespace Modules\PaymentMethod\Repositories;

use Modules\PaymentMethod\Models\PaymentMethod;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Model;

class PaymentMethodRepository extends BaseRepository
{
    protected Model $model;

    public function __construct(PaymentMethod $model)
    {
        $this->model = $model;
        parent::__construct($model);
    }

    /**
     * Get all active payment methods ordered by sort order.
     */
    public function getActiveOrdered()
    {
        return $this->model->active()->ordered()->get();
    }

    /**
     * Find payment method by code.
     */
    public function findByCode(string $code)
    {
        return $this->model->where('code', $code)->first();
    }
}
