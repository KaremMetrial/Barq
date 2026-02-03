<?php

namespace Modules\Promotion\Models;

use Modules\Promotion\Models\Promotion;
use Illuminate\Database\Eloquent\Model;
use Modules\Store\Models\Store;
use Modules\Product\Models\Product;

class PromotionFixedPrice extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'promotion_id',
        'store_id',
        'product_id',
        'fixed_price',
    ];
    protected $casts = [
        'fixed_price' => 'integer',
    ];
    public function promotion()
    {
        return $this->belongsTo(Promotion::class);
    }
    public function store()
    {
        return $this->belongsTo(Store::class);
    }
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
