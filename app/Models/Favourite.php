<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Favourite extends Model
{
    public $fillable = ['user_id'];
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function favouriteable(): MorphTo
    {
        return $this->morphTo();
    }
}
