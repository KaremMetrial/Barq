<?php

namespace App\Models;

use App\Enums\AddOnApplicableToEnum;
use Illuminate\Database\Eloquent\Model;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AddOn extends Model implements TranslatableContract
{
    use Translatable;
    public $translatedAttributes = ['name', 'description'];

    protected $fillable = [
        'price',
        'is_active',
        'applicable_to',
    ];
    protected $casts = [
        'is_active' => 'boolean',
        'applicable_to' => AddOnApplicableToEnum::class,
    ];
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'add_on_product');
    }
    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class,'add_on_order')
        ->withPivot('quantity','price');
    }
}
