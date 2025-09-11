<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Cviebrock\EloquentSluggable\Sluggable;

class Compaign extends Model implements TranslatableContract
{
    use Translatable, Sluggable;

    public $translatedAttributes = ['name', 'description'];

    protected $fillable = [
        'slug',
        'start_date',
        'end_date',
        'is_active',
    ];
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
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

    public function participations(): HasMany
    {
        return $this->hasMany(CompaignParicipation::class, 'compaign_id');
    }
}
