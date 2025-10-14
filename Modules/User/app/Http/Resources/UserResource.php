<?php

namespace Modules\User\Http\Resources;

use App\Enums\UserStatusEnum;
use App\Enums\UserTypeEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "name" => $this->first_name . ' ' . $this->last_name,
            "email" => $this->email,
            "phone" => $this->phone,
            "avatar" => $this->avatar ? asset("storage/" . $this->avatar) : null,
            "status" => $this->status->value,
            "status_label" => UserStatusEnum::label($this->status->value),
            "balance" => $this->balance,
            'address_id' => (int) $this->addresses()->first()->id,
        ];
    }
}
