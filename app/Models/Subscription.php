<?php

namespace App\Models;

use App\Enums\SubscriptionStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Subscription extends Model
{
    protected $fillable = [
        'start_date',
        'end_date',
        'status',
        'auto_renew',
        'is_active',
    ];
    protected $casts = [
        'status' => SubscriptionStatusEnum::class,
        'auto_renew' => 'boolean',
        'is_active' => 'boolean',
    ];
    public function subscriptionable(): MorphTo
    {
        return $this->morphTo();
    }
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
