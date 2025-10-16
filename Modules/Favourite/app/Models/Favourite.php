<?php

namespace Modules\Favourite\Models;

use Modules\User\Models\User;
use Modules\Store\Models\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Favourite extends Model
{
    public $fillable = ['user_id', 'favouriteable_id', 'favouriteable_type'];
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function favouriteable(): MorphTo
    {
        return $this->morphTo();
    }
}
