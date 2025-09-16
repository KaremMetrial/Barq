<?php

namespace App\Models;

use App\Enums\OptionInputTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
}
