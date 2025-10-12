<?php

namespace Modules\Conversation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'content'        => $this->content,
            'type'           => $this->type,
            'sender_id'      => $this->messageable_id,
            'sender_type'    => $this->messageable_type,
            'conversation_id'=> $this->conversation_id,
            'created_at'     => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
