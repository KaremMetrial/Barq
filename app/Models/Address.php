<?php

namespace App\Models;

use App\Enums\AddressTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;

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
}
