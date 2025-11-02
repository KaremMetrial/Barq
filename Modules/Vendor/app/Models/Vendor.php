<?php

namespace Modules\Vendor\Models;

use Modules\Store\Models\Store;
use Laravel\Sanctum\HasApiTokens;
use Modules\PosShift\Models\PosShift;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Vendor extends Authenticatable
{
    use SoftDeletes, HasApiTokens, Notifiable, HasRoles;
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'avatar',
        'password',
        'is_owner',
        'is_active',
        'store_id',
        'last_login',
    ];
    protected $casts = [
        'is_owner' => 'boolean',
        'is_active' => 'boolean',
        'password' => 'hashed'
    ];
    protected $hidden = [
        'password',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
    public function posShifts()
    {
        return $this->hasMany(PosShift::class);
    }
    public function scopeFilter($query, $filters)
    {
        if (isset($filters['store_id'])) {
            $query->where('store_id', $filters['store_id']);
        }
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }
        // if (isset($filters['role_id'])) {
        //     $query->whereHas('roles', function ($q) use ($filters) {
        //         $q->where('id', $filters['role_id']);
        //     });
        // }
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%$search%")
                    ->orWhere('last_name', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%")
                    ->orWhere('phone', 'like', "%$search%");
            });
        }
        return $query->latest();
    }
}
