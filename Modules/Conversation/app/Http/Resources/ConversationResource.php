<?php

namespace Modules\Conversation\Http\Resources;

use Illuminate\Http\Request;
use App\Enums\ConversationTypeEnum;
use App\Enums\ConversationStatusEnum;
use Modules\Store\Http\Resources\StoreResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Compaign\Http\Resources\CompaignResource;

class ConversationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id"           => $this->id,
            'type'         => $this->type->value,
            'type_label' => ConversationTypeEnum::label($this->type->value),
            'start_time'  => $this->start_time,
            'end_time'    => $this->end_time,
            'user_id'     => $this->user_id,
            'admin_id'    => $this->admin_id,
        ];
    }
}
