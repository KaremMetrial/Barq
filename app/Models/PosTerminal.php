<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosTerminal extends Model
{
    protected $table = "pos_terminals";
    protected $fillable = [
        "identifier",
        "name",
        "is_active",
        "store_id"
    ];
    protected $casts = [
        "is_active" => "boolean"
    ];
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
