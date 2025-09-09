<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeoTranslation extends Model
{
    public $timestamps = false;
    protected $fillable = ['meta_title', 'meta_description', 'meta_keywords', 'slug', 'locale', 'og_title', 'og_description'];
}
