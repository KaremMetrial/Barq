<?php

namespace Modules\Role\Http\Resources;

use App\Enums\SaleTypeEnum;
use App\Enums\RoleTypeEnum;
use Illuminate\Http\Request;
use App\Enums\RoleStatusEnum;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id"         => $this->id,
            "name"       => $this->name,
        ];
    }
}
