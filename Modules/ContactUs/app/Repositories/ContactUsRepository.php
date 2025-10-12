<?php

namespace Modules\ContactUs\Repositories;
use Modules\ContactUs\Models\ContactUs;
use Modules\ContactUs\Repositories\Contracts\ContactUsRepositoryInterface;
use App\Repositories\BaseRepository;
class ContactUsRepository extends BaseRepository implements ContactUsRepositoryInterface
{
    public function __construct(ContactUs $model)
    {
        parent::__construct($model);
    }
}
