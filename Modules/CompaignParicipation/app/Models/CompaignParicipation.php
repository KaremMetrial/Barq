<?php

namespace Modules\CompaignParicipation\Models;

use Modules\Store\Models\Store;
use Modules\Compaign\Models\Compaign;
use Illuminate\Database\Eloquent\Model;
use App\Enums\CompaignParicipationStatusEnum;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompaignParicipation extends Model
{
    protected $fillable = [
        'compaign_id',
        'store_id',
        'status',
        'notes',
        'responded_at',
        'points',
    ];
    protected $casts = [
        'responded_at' => 'date',
        'status' => CompaignParicipationStatusEnum::class,
    ];
    public function compaign(): BelongsTo
    {
        return $this->belongsTo(Compaign::class, 'compaign_id');
    }
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }
}
