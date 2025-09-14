<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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
}
