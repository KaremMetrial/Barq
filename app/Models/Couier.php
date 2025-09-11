<?php

namespace App\Models;

use App\Enums\UserStatusEnum;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use App\Enums\CouierAvaliableStatusEnum;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Couier extends Authenticatable
{
    use HasFactory, Notifiable,HasApiTokens;
    protected $fillable = [
        "first_name",
        "last_name",
        "email",
        "phone",
        "password",
        "avatar",
        "license_number",
        "avaliable_status",
        "avg_rate",
        "status",
    ];
    protected $casts = [
        "avaliable_status" => CouierAvaliableStatusEnum::class,
        "status" => UserStatusEnum::class,
        "password" => "hashed",
    ];
    protected $hidden = [
        "password",
    ];
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
