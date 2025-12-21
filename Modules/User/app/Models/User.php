<?php

namespace Modules\User\Models;

use App\Enums\UserStatusEnum;
use App\Events\UserCreated;
use App\Events\UserCreating;
use App\Models\NationalIdentity;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Address\Models\Address;
use Modules\Favourite\Models\Favourite;
use Modules\Interest\Models\Interest;
use Modules\Order\Enums\OrderStatus;
use Modules\Order\Models\Order;
use Modules\Store\Models\Store;
use Modules\Transaction\Models\Transaction;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'status',
        'device_token',
        'loyalty_points',
        'points_expire_at',
        'referral_code',
        'referral_id'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatusEnum::class,
            'points_expire_at' => 'datetime',
        ];
    }
    public function interests(): MorphMany
    {
        return $this->morphMany(Interest::class, 'interestable');
    }
    public function addresses(): MorphMany
    {
        return $this->morphMany(Address::class, 'addressable');
    }
    public function favourites()
    {
        return $this->hasMany(Favourite::class);
    }

    public static function isFavorite($favoritableId, $favoritableType)
    {
        if (!$token = request()->bearerToken()) {
            return false;
        }

        [, $tokenHash] = explode('|', $token, 2);

        $userId = DB::table('personal_access_tokens')
            ->where('token', hash('sha256', $tokenHash))
            ->value('tokenable_id');

        if (!$userId) {
            return false;
        }

        return Favourite::where('user_id', $userId)
            ->where('favoritable_id', $favoritableId)
            ->where('favoritable_type', $favoritableType)
            ->exists();
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function vendorStores()
    {
        return $this->hasMany(Store::class, 'vendor_id');
    }

    /**
     * Get user's total spending
     */
    public function getSpendingValueAttribute()
    {
        return $this->orders()->where('status', OrderStatus::DELIVERED)->sum('total_amount');
    }

    /**
     * Get user's available loyalty points
     */
    public function getAvailablePoints(): float
    {
        if (!$this->points_expire_at || (method_exists($this->points_expire_at, 'isPast') && $this->points_expire_at->isPast())) {
            return 0;
        }

        return $this->loyalty_points;
    }

    /**
     * Check if user has enough points for redemption
     */
    public function hasEnoughPoints(float $points): bool
    {
        return $this->getAvailablePoints() >= $points;
    }
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get the user's currency symbol based on their address
     */
    public function getCurrencySymbol()
    {
        $address = $this->addresses()->first();
        if ($address && $address->zone && $address->zone->city && $address->zone->city->governorate && $address->zone->city->governorate->country) {
            return $address->zone->city->governorate->country->currency_symbol;
        }
        return 'EGP'; // Default currency
    }
    
    /**
     * Get the user's country currency factor based on their address
     */
    public function getCountryCurrencyFactor()
    {
        $address = $this->addresses()->first();
        if ($address && $address->zone && $address->zone->city && $address->zone->city->governorate && $address->zone->city->governorate->country) {
            return $address->zone->city->governorate->country->currency_factor ?? 100;
        }
        return 100; // Default currency factor
    }
    
    public function referrer()
    {
        return $this->belongsTo(User::class, 'referral_id');
    }

    public function referrals()
    {
        return $this->hasMany(User::class, 'referral_id');
    }
    
    /**
     * Get user's rewards
     */
    public function rewards()
    {
        return $this->hasMany(\Modules\Reward\Models\Reward::class);
    }
}