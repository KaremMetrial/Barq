<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;

class PharmacyInfo extends Model implements TranslatableContract
{
    use Translatable;
    public $translatedAttributes = ['generic_name', 'common_use'];
    protected $fillable = [
        'prescription_required',
        'product_id'
    ];
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
