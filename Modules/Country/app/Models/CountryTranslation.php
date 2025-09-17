<?php

    namespace Modules\Country\Models;

    use Illuminate\Database\Eloquent\Model;

    class CountryTranslation extends Model
    {
        public $timestamps = false;
        protected $fillable = ['name'];
    }
