<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminNotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'body' => $this->body,
            'target_type' => $this->target_type,
            'target_data' => $this->target_data,
            'top_users_count' => $this->top_users_count,
            'performance_metric' => $this->performance_metric,
            'scheduled_at' => $this->scheduled_at,
            'sent_at' => $this->sent_at,
            'total_sent' => $this->total_sent,
            'total_failed' => $this->total_failed,
            'total_delivered' => $this->total_delivered,
            'admin_id' => $this->admin_id,
            'is_scheduled' => $this->is_scheduled,
            'is_sent' => $this->is_sent,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
