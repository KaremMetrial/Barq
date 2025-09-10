<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductValue extends Model implements TranslatableContract
{
    use Translatable;

    public $translatedAttributes = ['name'];

    protected $fillable = [
        'option_id',
        'price_modifier',
    ];
    public function option(): BelongsTo
    {
        return $this->belongsTo(Option::class);
    }
    public function productOptionValues(): HasMany
    {
        return $this->hasMany(ProductOptionValue::class);
    }
}
