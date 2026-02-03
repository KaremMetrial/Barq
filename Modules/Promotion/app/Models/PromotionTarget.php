<?php

namespace Modules\Promotion\Models;
use App\Enums\PromotionTargetTypeEnum;
use Illuminate\Database\Eloquent\Model;

class PromotionTarget extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'promotion_id',
        'target_type',
        'target_id',
        'is_excluded',
    ];
    protected $casts = [
        'target_type' => PromotionTargetTypeEnum::class,
        'is_excluded' => 'boolean',
    ];
    public function promotion()
    {
        return $this->belongsTo(Promotion::class);
    }
    public function target()
    {
        return $this->morphTo('target_type', 'target_id');
    }
}
