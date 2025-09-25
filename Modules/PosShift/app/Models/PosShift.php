<?php

namespace Modules\PosShift\Models;

use Modules\Cart\Models\Cart;
use Modules\Vendor\Models\Vendor;
use Illuminate\Database\Eloquent\Model;
use Modules\PosTerminal\Models\PosTerminal;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosShift extends Model
{
    protected $fillable = [
        "opened_at",
        "closed_at",
        "opening_balance",
        "closing_balance",
        "total_sales",
        "pos_terminal_id",
        "vendor_id"
    ];
    public function posTerminal(): BelongsTo
    {
        return $this->belongsTo(PosTerminal::class);
    }
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class);
    }
}
