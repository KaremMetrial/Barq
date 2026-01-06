<?php

namespace Modules\Role\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role as ModelsRole;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;

class Role extends ModelsRole
{
    public static function scopeFilter($query, $filters)
    {
        if (isset($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }
        if (isset($filters['guard_name'])) {
            $query->where('guard_name', $filters['guard_name']);
        }
        return $query;
    }
}
