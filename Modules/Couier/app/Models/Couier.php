<?php

namespace Modules\Couier\Models;

use App\Models\Attachment;
use App\Enums\UserStatusEnum;
use Modules\Zone\Models\Zone;
use Modules\Store\Models\Store;
use App\Models\NationalIdentity;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use App\Enums\CouierAvaliableStatusEnum;
use App\Enums\PlanTypeEnum;
use Illuminate\Notifications\Notifiable;
use Modules\Conversation\Models\Message;
use Modules\Conversation\Models\Conversation;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Modules\Order\Models\Order;

class Couier extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;
    protected $fillable = [
        "first_name",
        "last_name",
        "email",
        "phone",
        "password",
        "avatar",
        "license_number",
        "available_status",
        "avg_rate",
        "status",
        "store_id",
        "birthday",
        "commission_type",
        "commission_amount",
        "driving_license",
    ];
    protected $casts = [
        "avaliable_status" => CouierAvaliableStatusEnum::class,
        "status" => UserStatusEnum::class,
        "password" => "hashed",
        "commission_type" => PlanTypeEnum::class,
    ];
    protected $hidden = [
        "password",
    ];
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
    public function nationalIdentity(): MorphOne
    {
        return $this->morphOne(NationalIdentity::class, 'identityable');
    }
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachmentable');
    }
    public function vehicle(): HasOne
    {
        return $this->hasOne(CouierVehicle::class);
    }
    public function shifts(): HasMany
    {
        return $this->hasMany(CouierShift::class);
    }
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'couier_id');
    }

    public function messages(): MorphMany
    {
        return $this->morphMany(Message::class,'messageable');
    }
    public function zones()
    {
        return $this->belongsToMany(Zone::class, 'couier_zone', 'couier_id', 'zone_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'couier_id');
    }
}
