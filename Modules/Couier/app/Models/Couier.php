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
        "avaliable_status",
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

    /**
     * Get assigned shift templates for this courier
     */
    public function shiftTemplateAssignments(): HasMany
    {
        return $this->hasMany(CourierShiftTemplate::class, 'courier_id');
    }

    /**
     * Get active shift template assignments
     */
    public function activeShiftTemplates()
    {
        return $this->shiftTemplateAssignments()->active()->with('shiftTemplate.days');
    }

    /**
     * Get weekly schedule from assigned templates
     */
    public function getWeeklyScheduleAttribute(): array
    {
        $schedule = [];

        foreach ($this->activeShiftTemplates as $assignment) {
            foreach ($assignment->weekly_schedule as $day) {
                $dayKey = $day['day_of_week'];
                if (!isset($schedule[$dayKey])) {
                    $schedule[$dayKey] = $day;
                } else {
                    // If multiple templates assign same day, keep the first assignment
                    // Could implement conflict resolution logic here if needed
                }
            }
        }

        // Sort by day of week
        ksort($schedule);

        return array_values($schedule);
    }

    /**
     * Check if courier has any active shift template assignments
     */
    public function hasActiveAssignments(): bool
    {
        return $this->activeShiftTemplates()->exists();
    }

    /**
     * Get courier order assignments
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(\Modules\Couier\Models\CourierOrderAssignment::class);
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

    public function scopeFilter($query, $filters)
    {
        if (isset($filters['search'])) {
            $query->where('first_name', 'like', '%' . $filters['search'] . '%')
                ->orWhere('last_name', 'like', '%' . $filters['search'] . '%')
                ->orWhere('phone', 'like', '%' . $filters['search'] . '%');
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['avaliable_status'])) {
            $query->where('avaliable_status', $filters['avaliable_status']);
        }
        if(isset($filters['store_id'])){
            $query->where('store_id', $filters['store_id']);
        }
        return $query->latest();
    }

    /**
     * Generate authentication token for the courier.
     */
    public function generateToken($data = null)
    {
        $token = $this->createToken('auth_token', ['courier']);
        $token->accessToken->fcm_device = $data['fcm_device'] ?? null;
        $token->accessToken->save();
        return $token->plainTextToken;
    }
}
