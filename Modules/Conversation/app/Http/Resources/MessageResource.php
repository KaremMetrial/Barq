<?php

namespace Modules\Conversation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

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
            'conversation_id' => $message['conversation_id'],
            'created_at'     => $this->formatDate($this->created_at),
        ];
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
        $conversation = $this->conversation;
        if ($conversation && $conversation->user) {
            $address = $conversation->user->addresses()->first();
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
