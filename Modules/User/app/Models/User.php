<?php

namespace Modules\User\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Models\Transaction;
use App\Enums\UserStatusEnum;
use Modules\Cart\Models\Cart;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\DB;
use Modules\Address\Models\Address;
use Modules\Interest\Models\Interest;
use Modules\Favourite\Models\Favourite;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone_code',
        'phone',
        'avatar',
        'email_verified_at',
        'status',
        'provider',
        'provider_id',
        'balance',
        'referral_code',
        'referral_id',
        'password',
        'loyalty_points',
        'points_expire_at'
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
    public function interests(): BelongsToMany
    {
        return $this->belongsToMany(Interest::class);
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

        return DB::table('favourites')
            ->where('user_id', $userId)
            ->where('favouriteable_id', $favoritableId)
            ->where('favouriteable_type', $favoritableType)
            ->exists();
    }
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'user_id');
    }

    public function messages(): MorphMany
    {
        return $this->morphMany(Message::class, 'messageable');
    }
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class);
    }
    public function sharedCarts(): BelongsToMany
    {
        return $this->belongsToMany(Cart::class, 'cart_user');
    }
    public function generateToken()
    {
        return $this->createToken('api-user-token')->plainTextToken;
    }

    /**
     * Get loyalty transactions for the user
     */
    public function loyaltyTransactions()
    {
        return $this->hasMany(\App\Models\LoyaltyTransaction::class);
    }

    /**
     * Award loyalty points to user
     */
    public function awardPoints(float $points, string $description = null, $referenceable = null): bool
    {
        $settings = \App\Models\LoyaltySetting::getSettings();

        if (!$settings->isEnabled()) {
            return false;
        }

        $this->increment('loyalty_points', $points);

        // Update expiry date if needed
        if (!$this->points_expire_at || (method_exists($this->points_expire_at, 'isPast') && $this->points_expire_at->isPast())) {
            $this->points_expire_at = now()->addDays($settings->points_expiry_days);
            $this->save();
        }

        // Create transaction record
        $this->loyaltyTransactions()->create([
            'type' => 'earned',
            'points' => $points,
            'points_balance_after' => $this->loyalty_points,
            'description' => $description,
            'referenceable_type' => $referenceable ? get_class($referenceable) : null,
            'referenceable_id' => $referenceable ? $referenceable->id : null,
            'expires_at' => $this->points_expire_at,
        ]);

        return true;
    }

    /**
     * Redeem loyalty points
     */
    public function redeemPoints(float $points, string $description = null, $referenceable = null): bool
    {
        if ($this->loyalty_points < $points) {
            return false;
        }

        $this->decrement('loyalty_points', $points);

        // Create transaction record
        $this->loyaltyTransactions()->create([
            'type' => 'redeemed',
            'points' => -$points, // Negative for redemption
            'points_balance_after' => $this->loyalty_points,
            'description' => $description,
            'referenceable_type' => $referenceable ? get_class($referenceable) : null,
            'referenceable_id' => $referenceable ? $referenceable->id : null,
        ]);

        return true;
    }

    /**
     * Get available loyalty points (non-expired)
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
    public function getCurrencySymbol(): string
    {
        $address = $this->addresses()->first();
        if ($address && $address->zone && $address->zone->city && $address->zone->city->governorate && $address->zone->city->governorate->country) {
            return $address->zone->city->governorate->country->currency_symbol;
        }
        return 'EGP'; // Default currency
    }
}
