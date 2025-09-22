<?php

namespace Modules\PosTerminal\Http\Resources;

use App\Enums\SaleTypeEnum;
use App\Enums\PosTerminalTypeEnum;
use Illuminate\Http\Request;
use App\Enums\PosTerminalStatusEnum;
use Illuminate\Http\Resources\Json\JsonResource;

class PosTerminalResource extends JsonResource
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
            "identifier" => $this->identifier,
            "name"       => $this->name,
            "is_active"  => (bool) $this->is_active,
            "store"      => $this->whenLoaded('store', function () {
                return [
                    "id"   => $this->store->id,
                    "name" => $this->store->name,
                ];
            }),
        ];
    }
}
