<?php

namespace Modules\Slider\Models;

use Illuminate\Database\Eloquent\Model;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

class Slider extends Model implements TranslatableContract
{
    use Translatable;
    protected $with = ['translations'];

    public $translatedAttributes = ['title', 'body'];

    protected $fillable = [
        'image',
        'button_text',
        'target',
        'target_id',
        'is_active',
        'sort_order'
    ];
    protected $casts = [
        'is_active' => 'boolean',
    ];
    /*
        * Scopes Query to get Only Active Country
    */
    #[Scope]
    protected function active(Builder $query)
    {
        return $this->where('is_active', true);
    }
    public function scopeFilter($query, $filters)
    {
        if (!auth('admin')->check()) {
            $query->whereIsActive(true);
        }
        return $query->orderBy('sort_order', 'asc');
    }
}
