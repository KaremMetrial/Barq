<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Astrotomic\Translatable\Translatable;
use Cviebrock\EloquentSluggable\Sluggable;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;

class Vehicle extends Model implements TranslatableContract
{
    use Translatable, Sluggable;

    public $translatedAttributes = ['name', 'description'];

    protected $fillable = ['slug', 'is_active'];
    protected $casts = [
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
}
