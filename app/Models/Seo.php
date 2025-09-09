<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Cviebrock\EloquentSluggable\Sluggable;

class Seo extends Model implements TranslatableContract
{
    use Translatable, Sluggable;
    public $translatedAttributes = ['meta_title', 'meta_description', 'meta_keywords', 'slug', 'locale', 'og_title', 'og_description'];
    protected $fillable = [
        'canonical_url',
        'image',
        'seoble_id',
        'seoble_type',
    ];
    public function seo(): MorphTo
    {
        return $this->morphTo();
    }
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'meta_title'
            ]
        ];
    }
}
