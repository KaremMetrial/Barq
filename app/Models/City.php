<?php

    namespace App\Models;

    use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
    use Astrotomic\Translatable\Translatable;
    use Illuminate\Database\Eloquent\Attributes\Scope;
    use Illuminate\Database\Eloquent\Builder;
    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Database\Eloquent\Relations\BelongsTo;

    class City extends Model implements TranslatableContract
    {
        use Translatable;

        public $translatedAttributes = ['name'];

        protected $fillable = [
            'governorate_id',
            'is_active',
        ];
        protected $casts = [
            'is_active' => 'boolean',
        ];
        /*
        * Scopes Query to search by name
        */
        #[Scope]
        public function searchName(Builder $query, string $search): Builder
        {
            return $query->whereTranslationLike('name', "%{$search}%");
        }

        /*
        * Relationship To Governorate
        */
        public function governorate(): BelongsTo
        {
            return $this->belongsTo(Governorate::class);
        }
    }
