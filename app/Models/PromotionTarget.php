<?php

namespace App\Models;

use App\Enums\PromotionTargetTypeEnum;
use Illuminate\Database\Eloquent\Model;

class PromotionTarget extends Model
{
    protected $fillable = [
        'promotion_id',
        'target_type',
        'target_id',
        'is_excluded',
    ];
    protected $casts = [
        'target_type' => PromotionTargetTypeEnum::class,
        'is_excluded' => 'boolean',
        'target_id' => 'bigint',
    ];
    public function promotion()
    {
        return $this->belongsTo(Promotion::class);
    }

}
