<?php

namespace App\Models;

use App\Enums\ReportTypeEnum;
use App\Enums\ReportStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Report extends Model
{
    protected $fillable = [
        'type',
        'resolved_at',
        'priority',
        'status',
        'description',
        'store_id',
        'product_id',
        'order_id'
    ];
    protected $casts = [
        'type' => ReportTypeEnum::class,
        'status' => ReportStatusEnum::class
    ];
    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
