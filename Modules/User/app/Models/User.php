<?php

namespace Modules\User\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Enums\UserStatusEnum;
use Modules\Cart\Models\Cart;
use Laravel\Sanctum\HasApiTokens;
use Modules\Address\Models\Address;
use Modules\Interest\Models\Interest;
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
        'name',
        'email',
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
        'otp_hash',
        'otp_expires_at',
        'otp_verified_at'
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
    /**
     * Check if the given store or product is a favorite of the user.
     *
     * @param  int    $favoritableId
     * @param  string $favoritableType
     * @return bool
     */
    public function isFavorite($favoritableId, $favoritableType)
    {
        // Check if the store or product is in the user's favorites
        return $this->favorites()
            ->where('favoritable_id', $favoritableId)
            ->where('favoritable_type', $favoritableType)
            ->exists();
    }
    public function conversations() : HasMany
    {
        return $this->hasMany(Conversation::class, 'user_id');
    }

    public function messages(): MorphMany
    {
        return $this->morphMany(Message::class,'messageable');
    }
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class);
    }
}
