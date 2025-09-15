<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;

class ProductAllergen extends Model implements TranslatableContract
{
    use Translatable;
    public $translatedAttributes = ['name'];

    protected $fillable = ['product_id'];
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
