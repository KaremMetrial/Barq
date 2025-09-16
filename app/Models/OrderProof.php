<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class OrderProof extends Model
{
    protected $fillable = [
        "image_url",
        "order_id"
    ];
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
    public function orderProofable(): MorphTo
    {
        return $this->morphTo();
    }
}
