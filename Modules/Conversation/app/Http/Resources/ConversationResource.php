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
use Carbon\Carbon;

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
            'start_time'  => $this->formatDate($this->start_time),
            'end_time'    => $this->formatDate($this->end_time),
            'user_id'     => $this->user_id,
            'vendor_id'   => $this->vendor_id,
            'admin_id'    => $this->admin_id,
            'couier_id'   => $this->couier_id,
            'store_id'    => $this->store_id,
            'last_message' => $this->last_message ? new MessageResource($this->last_message) : null,
            'status'      => $this->getStatus(),
            'user' => $this->user ? new UserResource($this->whenLoaded('user')) : null,
            'vendor' => $this->vendor ? new VendorResource($this->whenLoaded('vendor')) : null,
            'admin' => $this->admin ? new AdminResource($this->whenLoaded('admin')) : null,
            'couier' => $this->couier ? new CouierResource($this->whenLoaded('couier')) : null,
            'store' => $this->store ? new StoreResource($this->whenLoaded('store')) : null,
        ];
    }
    public function getStatus()
    {
        if ($this->end_time) {
            $status = 'ENDED';
        } else if ($this->admin_id && $this->end_time == null) {
            $status = 'ACTIVE';
        } else {
            $status = 'PENDING';
        }
        return $status;
    }

    private function formatDate($date)
    {
        if (!$date) {
            return null;
        }

        $timezone = $this->getTimezone();

        return Carbon::parse($date)->setTimezone($timezone)->toDateTimeString();
    }

    private function getTimezone()
    {
        if ($this->user) {
            $address = $this->user->addresses()->first();
            if ($address && $address->zone && $address->zone->city && $address->zone->city->governorate && $address->zone->city->governorate->country) {
                $timezone = $address->zone->city->governorate->country->timezone;
                if ($timezone) {
                    return $timezone;
                }
            }
        }

        return config('settings.timezone', config('app.timezone', 'UTC'));
    }
}
