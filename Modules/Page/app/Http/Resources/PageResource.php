<?php

namespace Modules\Page\Http\Resources;

use App\Enums\SaleTypeEnum;
use App\Enums\PageTypeEnum;
use Illuminate\Http\Request;
use App\Enums\PageStatusEnum;
use Illuminate\Http\Resources\Json\JsonResource;

class PageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id"=> $this->id,
            "title"=> $this->title,
            "slug"=> $this->slug,
            "content"=> $this->content,
            "is_active"=> (bool)$this->is_active,
        ];
    }
}
