<?php

namespace App\Models;

use App\Enums\PlanTypeEnum;
use App\Enums\PlanBillingCycleEnum;
use Illuminate\Database\Eloquent\Model;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;

class Plan extends Model implements TranslatableContract
{
    use Translatable;
    public $translatedAttributes = ['name', 'description'];

    protected $fillable = [
        'price',
        'commission_rate',
        'vehicle_limit',
        'order_limit',
        'billing_cycle',
        'type',
        'is_active',
    ];
    protected $casts = [
        'billing_cycle' => PlanBillingCycleEnum::class,
        'type' => PlanTypeEnum::class,
        'is_active' => 'boolean'
    ];
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
