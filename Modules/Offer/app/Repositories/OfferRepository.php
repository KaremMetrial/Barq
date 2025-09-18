<?php

namespace Modules\Offer\Repositories;
use Modules\Offer\Models\Offer;
use Modules\Offer\Repositories\Contracts\OfferRepositoryInterface;
use App\Repositories\BaseRepository;
class OfferRepository extends BaseRepository implements OfferRepositoryInterface
{
    public function __construct(Offer $model)
    {
        parent::__construct($model);
    }
}
