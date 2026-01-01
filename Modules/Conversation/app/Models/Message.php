<?php

namespace Modules\Conversation\Models;

use App\Enums\MessageTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Conversation\Observers\MessageObserver;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

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
        'is_read',
    ];

    protected $casts = [
        'read_by' => 'array',
        'type' => MessageTypeEnum::class,
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
