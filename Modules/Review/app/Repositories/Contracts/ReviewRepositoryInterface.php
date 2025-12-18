<?php

namespace Modules\Review\Repositories\Contracts;

use App\Repositories\Contracts\BaseRepositoryInterface;

interface ReviewRepositoryInterface extends BaseRepositoryInterface
{
    public function getReviewsByOrderId(int $orderId);
    public function getReviewForStore(int $storeId, array $filters = []);
    public function getReviewStatsForStore(int $storeId, array $filters = []): array;
}
