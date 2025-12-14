<?php

namespace Modules\Conversation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $message = $this->resource->toArray();

        return [
            'id'             => $message['id'],
            'content'        => $message['content'],
            'type'           => $message['type'],
            'sender_id'      => $message['messageable_id'],
            'sender_type'    => $message['messageable_type'],
            'conversation_id'=> $message['conversation_id'],
            'created_at'     => $message['created_at'],
        ];

    }
}
