<?php

namespace App\Models;

use App\Enums\WorkingDayEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkingDay extends Model
{
    protected $fillable = [
        'day_of_week',
        'open_time',
        'close_time',
        'rental_shop_id',
    ];

    protected $casts = [
        'day_of_week' => WorkingDayEnum::class,
        'open_time' => 'datetime:H:i',
        'close_time' => 'datetime:H:i',
    ];
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
