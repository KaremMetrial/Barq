<?php

    namespace App\Models;

    use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
    use Astrotomic\Translatable\Translatable;
    use Illuminate\Database\Eloquent\Attributes\Scope;
    use Illuminate\Database\Eloquent\Builder;
    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Database\Eloquent\Relations\HasMany;
    use Illuminate\Database\Eloquent\Relations\HasManyThrough;

    class Country extends Model implements TranslatableContract
    {
        use Translatable;

        public $translatedAttributes = ['name'];
        protected $fillable = [
            'code',
            'is_active',
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

        /*
         * Scopes Query to search by name
         */
        #[Scope]
        public function searchName(Builder $query, string $search): Builder
        {
            return $query->whereTranslationLike('name', "%{$search}%");
        }

        /*
         * Relationship to Governorates
         */
        public function governorates(): HasMany
        {
            return $this->hasMany(Governorate::class);
        }

        /*
         * Relationship to Cities through Governorates
         */
        public function cities(): HasManyThrough
        {
            return $this->hasManyThrough(City::class, Governorate::class);
        }
    }
