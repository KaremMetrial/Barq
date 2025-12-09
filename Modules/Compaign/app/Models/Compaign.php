<?php

namespace Modules\Compaign\Models;

use Illuminate\Database\Eloquent\Model;

use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Cviebrock\EloquentSluggable\Sluggable;
use Modules\CompaignParicipation\Models\CompaignParicipation;

class Compaign extends Model implements TranslatableContract
{
    use Translatable, Sluggable;

    public $translatedAttributes = ['name', 'description'];

    protected $fillable = [
        'slug',
        'start_date',
        'end_date',
        'is_active',
        'reward_id',
    ];

    public function reward()
    {
        return $this->belongsTo(\Modules\Reward\Models\Reward::class);
    }
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
