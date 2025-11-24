<?php

namespace Modules\Couier\Models;

use Modules\Vehicle\Models\Vehicle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CouierVehicle extends Model
{
    protected $fillable = [
        "plate_number",
        "color",
        "model",
        "courier_id",
        "vehicle_id",
        "car_license",
    ];
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
    public function courier(): BelongsTo
    {
        return $this->belongsTo(Couier::class);
    }
}
