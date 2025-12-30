<?php

namespace Modules\Conversation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Modules\Conversation\Observers\MessageObserver;

#[ObservedBy([MessageObserver::class])]
class Message extends Model
{
    protected $fillable = [
        'content',
        'type',
        'conversation_id',
        'messageable_type',
        'messageable_id',
        'read_at',
        'read_by',
    ];

    protected $casts = [
        'read_by' => 'array',
    ];
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    public function sender(): MorphTo
    {
        return $this->morphTo();
    }

}
