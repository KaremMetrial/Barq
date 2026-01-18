<?php

namespace App\Models;

use App\Enums\TransactionType;
use App\Enums\TransactionStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Astrotomic\Translatable\Translatable;
use Modules\PaymentMethod\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;

class Transaction extends Model implements TranslatableContract
{
    use Translatable;

    public $translatedAttributes = ['description'];
    protected $fillable = [
        'user_id',
        'transactionable_type',
        'transactionable_id',
        'type',
        'amount',
        'currency',
        'payment_method_id',
        'status',
        'order_id'
    ];

    protected $casts = [
        'amount' => 'decimal:3',
        'type' => TransactionType::class,
        'status' => TransactionStatusEnum::class,
    ];

    public function transactionable(): MorphTo
    {
        return $this->morphTo();
    }

    // Maintain backward compatibility with user relationship
    public function user()
    {
        return $this->belongsTo(\Modules\User\Models\User::class);
    }

    // Helper methods to get the related entity
    public function getEntityAttribute()
    {
        return $this->transactionable_type ? $this->transactionable : $this->user;
    }

    // Scope to filter by entity type
    public function scopeForEntity($query, $entityType, $entityId)
    {
        return $query->where('transactionable_type', $entityType)
                    ->where('transactionable_id', $entityId);
    }

    // Scope to filter by user for backward compatibility
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Scope to filter by store
    public function scopeForStore($query, $storeId)
    {
        return $query->where('transactionable_type', 'store')
                    ->where('transactionable_id', $storeId);
    }

    // Scope to filter by courier
    public function scopeForCourier($query, $courierId)
    {
        return $query->where('transactionable_type', 'courier')
                    ->where('transactionable_id', $courierId);
    }
    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }
}
