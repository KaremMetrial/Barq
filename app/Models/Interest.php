<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Astrotomic\Translatable\Translatable;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;

class Interest extends Model implements TranslatableContract
{
    use Sluggable, Translatable;

    public $translatedAttributes = ['name'];

    protected $fillable = [
        'slug',
        'is_active',
        'icon',
        'category_id'
    ];
    protected $casts = [
        'is_active'=> 'boolean',
    ];
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'name'
            ]
        ];
    }
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
