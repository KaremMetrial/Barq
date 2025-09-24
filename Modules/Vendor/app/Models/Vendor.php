<?php

namespace Modules\Vendor\Models;

use Modules\Store\Models\Store;
use Laravel\Sanctum\HasApiTokens;
use Modules\PosShift\Models\PosShift;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Vendor extends Authenticatable
{
    use SoftDeletes, HasApiTokens, Notifiable;
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'avatar',
        'password',
        'is_owner',
        'is_active',
        'store_id'
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
}
