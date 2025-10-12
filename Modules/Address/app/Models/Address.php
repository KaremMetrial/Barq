<?php

namespace Modules\Address\Models;

use Modules\Zone\Models\Zone;
use App\Enums\AddressTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;

class Address extends Model implements TranslatableContract
{
    use Translatable;

    public $translatedAttributes = ['address_line_1', 'address_line_2'];

    protected $fillable = [
        'latitude',
        'longitude',
        'name',
        'phone',
        'is_default',
        'type',
        'zone_id',
        'addressable_type',
        'addressable_id',
        'city_id',
        'governorate_id',
        'country_id'
    ];
    protected $casts = [
        'is_default' => 'boolean',
        'type' => AddressTypeEnum::class,
    ];
    public function addressable(): MorphTo
    {
        return $this->morphTo();
    }
    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }
    public function scopeFilter($query, $filters)
    {
        $user = auth('user')->user();
        if ($user) {
            $query->where('addressable_id', $user->id)
                ->where('addressable_type', 'user');
        }
        return $query->latest();
    }
}
