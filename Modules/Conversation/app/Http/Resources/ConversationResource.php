<?php

namespace Modules\Conversation\Http\Resources;

use Illuminate\Http\Request;
use App\Enums\ConversationTypeEnum;
use App\Enums\ConversationStatusEnum;
use Modules\User\Http\Resources\UserResource;
use Modules\Admin\Http\Resources\AdminResource;
use Modules\Store\Http\Resources\StoreResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Couier\Http\Resources\CouierResource;
use Modules\Vendor\Http\Resources\VendorResource;
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
            'user_id'     => $this->user_id ? new UserResource($this->whenLoaded('user')) : null,
            'vendor_id'   => $this->vendor_id ? new VendorResource($this->whenLoaded('vendor')) : null,
            'admin_id'    => $this->admin_id ? new AdminResource($this->whenLoaded('admin')) : null,
            'couier_id'   => $this->couier_id ? new CouierResource($this->whenLoaded('couier')) : null,
            'last_message' => $this->last_message,
        ];
    }
}
