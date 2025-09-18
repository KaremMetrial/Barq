<?php

namespace Modules\Compaign\Models;

use Illuminate\Database\Eloquent\Model;

class CompaignTranslation extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'name',
        'description',
    ];
}
