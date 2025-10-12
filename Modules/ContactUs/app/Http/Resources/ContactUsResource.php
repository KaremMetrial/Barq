<?php

namespace Modules\ContactUs\Http\Resources;

use Illuminate\Http\Request;
use App\Enums\ContactUsStatusEnum;
use Modules\Store\Http\Resources\StoreResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Compaign\Http\Resources\CompaignResource;

class ContactUsResource extends JsonResource
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
            'phone'        => $this->phone,
            'content'      => $this->content
        ];
    }
}
