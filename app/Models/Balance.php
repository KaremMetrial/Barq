<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Balance extends Model
{
    protected $fillable = [
        'available_balance',
        'pending_balance',
        'total_balance',
        'balanceable_id',
        'balanceable_type',
    ];
    protected $casts = [
        'available_balance' => 'decimal:3',
        'pending_balance' => 'decimal:3',
        'total_balance' => 'decimal:3',
    ];
    public function balanceable(): MorphTo
    {
        return $this->morphTo();
    }
}
