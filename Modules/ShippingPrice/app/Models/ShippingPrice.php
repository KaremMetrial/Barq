<?php

namespace Modules\ShippingPrice\Models;

use Modules\Zone\Models\Zone;
use Modules\Vehicle\Models\Vehicle;
use Illuminate\Database\Eloquent\Model;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;

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
    public function scopeFilter($query, $filters)
    {
        return $query->latest();
    }
}
