<?php

namespace Modules\Option\Models;

use App\Enums\OptionInputTypeEnum;
use Modules\Product\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Modules\Product\Models\ProductValue;
use Astrotomic\Translatable\Translatable;
use Modules\Product\Models\ProductOption;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;

class Option extends Model implements TranslatableContract
{
    use Translatable;

    public $translatedAttributes = ['name'];

    protected $fillable = [
        'input_type',
        'is_food_option',
    ];
    protected $casts = [
        'is_food_option' => 'boolean',
        'input_type' => OptionInputTypeEnum::class,
    ];
    public function values(): HasMany
    {
        return $this->hasMany(ProductValue::class);
    }
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_options')
            ->using(ProductOption::class)
            ->withPivot(['min_select','max_select','is_required','sort_order']);
    }
    public function productOptions()
    {
        return $this->hasMany(ProductOption::class);
    }
}
