<?php

namespace Modules\Withdrawal\Http\Resources;

use App\Enums\WithdrawalStatusEnum;
use App\Enums\WithdrawalTypeEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WithdrawalResource extends JsonResource
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
            'withdrawable_id' => $this->withdrawable_id,
            'withdrawable_type' => $this->withdrawable_type,
            'amount' => $this->amount,
            'currency_code' => $this->currency_code,
            'currency_factor' => $this->currency_factor,
            'status' => $this->status,
            'notes' => $this->notes,
            'bank_name' => $this->bank_name,
            'account_number' => $this->account_number,
            'iban' => $this->iban,
            'swift_code' => $this->swift_code,
            'account_holder_name' => $this->account_holder_name,
            'processed_at' => $this->processed_at,
            'processed_by' => $this->processed_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'withdrawable' => $this->whenLoaded('withdrawable', function () {
                $withdrawable = $this->withdrawable;
                return [
                    'id' => $withdrawable->id,
                    'type' => class_basename($withdrawable),
                    'name' => $this->getEntityName($withdrawable),
                ];
            }),

        ];
    }
        private function getEntityName($entity): string
    {
        if (!$entity) {
            return 'N/A';
        }

        // Different entity types may have different name fields
        if (method_exists($entity, 'getName')) {
            return $entity->getName();
        } elseif (isset($entity->name)) {
            return is_array($entity->name) ? ($entity->name['en'] ?? $entity->name[0] ?? 'N/A') : $entity->name;
        } elseif (isset($entity->title)) {
            return $entity->title;
        } elseif (isset($entity->first_name) && isset($entity->last_name)) {
            return $entity->first_name . ' ' . $entity->last_name;
        } elseif (isset($entity->email)) {
            return $entity->email;
        } elseif (isset($entity->phone)) {
            return $entity->phone;
        }

        return get_class($entity) . ' #' . $entity->id;
    }

}
