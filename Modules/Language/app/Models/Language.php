<?php

namespace Modules\Language\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Modules\Language\Observers\LanguageObserver;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

#[ObservedBy([LanguageObserver::class])]
class Language extends Model
{
    protected $fillable = [
        'name',
        'code',
        'native_name',
        'direction',
        'is_default',
        'is_active',
    ];
    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    /*
         * Scopes Query to get Only Active Language
         */
    #[Scope]
    protected function active(Builder $query)
    {
        return $query->where('is_active', true);
    }
}
