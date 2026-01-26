<?php

namespace Modules\Promotion\Models;
use Illuminate\Database\Eloquent\Model;
use Modules\User\Models\User;

class UserPromotionUsage extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'promotion_id',
        'user_id',
        'usage_count',
        'last_used_at',
    ];
    protected $casts = [
        'usage_count' => 'integer',
        'last_used_at' => 'datetime',
    ];
    public function promotion()
    {
        return $this->belongsTo(Promotion::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
