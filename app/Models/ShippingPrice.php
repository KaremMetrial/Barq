<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;

class ShippingPrice extends Model implements TranslatableContract
{
    use Translatable;
    public $translatedAttributes = ['name'];
    protected $fillable = [
        'base_price',
        'max_price',
        'per_km_price',
        'max_cod_price',
        'enable_cod',
        'zone_id',
        'vehicle_id'
    ];
    protected $casts = [
        'enable_cod' => 'boolean',
    ];
    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
