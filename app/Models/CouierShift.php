<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CouierShift extends Model
{
    protected $fillable = [
        'start_time',
        'end_time',
        'is_open',
        'couier_id'
    ];
    protected $casts = [
        'is_open' => 'boolean'
    ];
    public function couier(): BelongsTo
    {
        return $this->belongsTo(Couier::class);
    }
}
