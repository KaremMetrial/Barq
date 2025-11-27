<?php

namespace App\Models;

use Modules\Store\Models\Store;
use Modules\Product\Models\Product;
use Illuminate\Database\Eloquent\Model;

class DeepLinkToken extends Model
{
    protected $table = 'deeplink_tokens';
    protected $fillable = [
        'token',
        'type',
        'target_id',
        'referrer_code',
        'platform',
        'status',
        'click_ip',
        'clicked_at'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'target_id');
    }
    public function store()
    {
        return $this->belongsTo(Store::class, 'target_id');
    }
}
