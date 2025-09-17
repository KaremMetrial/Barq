<?php

namespace Modules\Unit\Models;

use App\Enums\UnitTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;

class Unit extends Model implements TranslatableContract
{
    use Translatable;

    public $translatedAttributes = ['name', 'abbreviation'];

    protected $fillable = [
        'type',
        'is_base',
        'conversion_to_base',
    ];
    protected $casts = [
        'is_base' => 'boolean',
        'type' => UnitTypeEnum::class
    ];
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)
                ->withPivot('unit_value');
    }
}
