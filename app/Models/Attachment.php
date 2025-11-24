<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Attachment extends Model
{
    protected $fillable = [
        "path",
        "name",
        "_type",
        "_id"
    ];
    public function attachmentable(): MorphTo
    {
        return $this->morphTo();
    }
}
