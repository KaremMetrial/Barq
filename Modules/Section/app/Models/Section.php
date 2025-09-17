<?php

namespace Modules\Section\Models;

use App\Enums\SectionTypeEnum;
use Modules\Category\Models\Category;
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
        'type'
    ];

    protected $casts = [
        'is_restaurant' => 'boolean',
        'is_active' => 'boolean',
        'type' => SectionTypeEnum::class,
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
