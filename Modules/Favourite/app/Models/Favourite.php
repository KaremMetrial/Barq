<?php

namespace Modules\Favourite\Models;

use Modules\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
