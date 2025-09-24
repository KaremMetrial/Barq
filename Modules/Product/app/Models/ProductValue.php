<?php

namespace Modules\Product\Models;

use Modules\Option\Models\Option;
use Illuminate\Database\Eloquent\Model;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;

class ProductValue extends Model implements TranslatableContract
{
    use Translatable;

    public $translatedAttributes = ['name'];

    protected $fillable = [
        'option_id',
    ];
    public function optionValues(): HasMany
    {
        return $this->hasMany(ProductOptionValue::class, 'product_value_id');
    }

    public function productOption(): BelongsTo
    {
        return $this->belongsTo(ProductOption::class, 'option_id');
    }

}
