<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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
}
