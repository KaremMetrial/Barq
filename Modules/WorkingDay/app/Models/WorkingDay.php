<?php

namespace Modules\WorkingDay\Models;

use App\Enums\WorkingDayEnum;
use Modules\Store\Models\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkingDay extends Model
{
    protected $fillable = [
        'day_of_week',
        'open_time',
        'close_time',
        'store_id',
    ];

    protected $casts = [
        'day_of_week' => WorkingDayEnum::class,
    ];
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
    public function scopeFilter($query, $filters)
    {
        if (auth('vendor')->check()) {
            return $query->where('store_id', auth('vendor')->user()->store_id);
        }

        return $query->when($filters['store_id'] ?? null, function ($query) use ($filters) {
            return $query->where('store_id', $filters['store_id']);
        });
    }
}
