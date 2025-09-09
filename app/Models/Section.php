<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Astrotomic\Translatable\Translatable;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;

class Section extends Model implements TranslatableContract
{
    use Translatable, Sluggable;

    public $translatedAttributes = ['name', 'description'];

    protected $fillable = [
        'slug',
        'icon',
        'is_restaurant',
        'is_active',
    ];

    protected $casts = [
        'is_restaurant' => 'boolean',
        'is_active' => 'boolean',
    ];
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'name'
            ]
        ];
    }
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_section');
    }
}
