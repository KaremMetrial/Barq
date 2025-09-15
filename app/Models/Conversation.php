<?php

namespace App\Models;

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
    public function order (): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class,'user_id');
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

}
