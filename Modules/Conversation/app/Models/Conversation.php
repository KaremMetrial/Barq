<?php

namespace Modules\Conversation\Models;

use App\Enums\ConversationTypeEnum;
use Modules\User\Models\User;
use Modules\Admin\Models\Admin;
use Modules\Order\Models\Order;
use Modules\Couier\Models\Couier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Conversation extends Model
{
    protected $fillable = [
        'type',
        'start_time',
        'end_time',
        'user_id',
        'admin_id',
        'couier_id',
        'order_id'
    ];
    protected $casts = [
        'type' => ConversationTypeEnum::class,
    ];
    public function order (): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class,'user_id');
    }
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class,'vendor_id');
    }
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class,'admin_id');
    }
    public function couier(): BelongsTo
    {
        return $this->belongsTo(Couier::class,'couier_id');
    }
    public function messages()
    {
        return $this->hasMany(Message::class, 'conversation_id');
    }
    public static function scopeFilter($query, $filters): mixed
    {
        if(isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if(isset($filters['user_agent'])) {
            switch ($filters['user_agent']) {
                case 'user':
                    $query->whereNotNull('user_id');
                    break;
                case 'vendor':
                    $query->whereNotNull('vendor_id');
                    break;
                case 'admin':
                    $query->whereNotNull('admin_id');
                    break;
                case 'courier':
                    $query->whereNotNull('couier_id');
                    break;
            }
        }
        return $query->latest();
    }
    public function getLastMessageAttribute()
    {
        return $this->messages()->latest()->first();
    }
}
