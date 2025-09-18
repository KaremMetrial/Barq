<?php

    namespace Modules\City\Models;

    use Illuminate\Database\Eloquent\Model;

    class CityTranslation extends Model
    {
        public $timestamps = false;
        protected $fillable = [
            'name',
        ];
    }
