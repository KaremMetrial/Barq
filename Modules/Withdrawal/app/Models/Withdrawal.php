<?php

namespace Modules\Withdrawal\Models;

use Modules\Admin\Models\Admin;
use App\Enums\WithdrawalStatusEnum;
use Illuminate\Database\Eloquent\Model;

class Withdrawal extends Model
{
    protected $fillable = [
        'withdrawable_id',
        'withdrawable_type',
        'amount',
        'currency_code',
        'currency_factor',
        'status',
        'notes',
        'bank_name',
        'account_number',
        'iban',
        'swift_code',
        'account_holder_name',
        'processed_at',
        'processed_by',
    ];
    protected $casts = [
        'status' => WithdrawalStatusEnum::class
    ];
    public function withdrawable()
    {
        return $this->morphTo();
    }
    public function admin()
    {
        return $this->belongsTo(Admin::class, 'processed_by');
    }
    public function scopeFilter($query, $filter)
    {
        $query->latest();
    }
        public function user()
    {
        return $this->belongsTo(\Modules\User\Models\User::class);
    }
    public function getEntityAttribute()
    {
        return $this->withdrawable_type ? $this->withdrawable : $this->user;
    }
    public function scopeForEntity($query, $entityType, $entityId)
    {
        return $query->where('withdrawable_type', $entityType)
                    ->where('withdrawable_id', $entityId);
    }
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Scope to filter by store
    public function scopeForStore($query, $storeId)
    {
        return $query->where('withdrawable_type', 'store')
                    ->where('withdrawable_id', $storeId);
    }

    // Scope to filter by courier
    public function scopeForCourier($query, $courierId)
    {
        return $query->where('withdrawable_type', 'courier')
                    ->where('withdrawable_id', $courierId);
    }

}
