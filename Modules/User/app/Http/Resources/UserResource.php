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
            "first_name" => $this->first_name,
            "last_name" => $this->last_name,
            "email" => $this->email,
            "phone" => $this->phone,
            "phone_code" => $this->phone_code,
            "avatar" => $this->avatar ? asset("storage/" . $this->avatar) : null,
            "status" => $this->status->value,
            "status_label" => UserStatusEnum::label($this->status->value),
            "balance" => $this->balance,
            'loyalty_points' => $this->loyalty_points,
            'available_loyalty_points' => $this->getAvailablePoints(),
            'points_expire_at' => $this->points_expire_at,
            'address_id' => (int) $this->addresses()->first()->id,
        ];
    }
}
