<?php

namespace Modules\ContactUs\Models;

use Illuminate\Database\Eloquent\Model;

class ContactUs extends Model
{
    protected $table = "contact_us";
    protected $fillable = [
        "phone",
        "content"
    ];
    public function scopeFilter($query, $filters)
    {
        return $query->latest();
    }
}
