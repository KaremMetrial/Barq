<?php

namespace App\Http\Resources;

use App\Enums\TransactionStatusEnum;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Enums\TransactionType;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->first_name . ' ' . $this->user->last_name,
                    'email' => $this->user->email,
                ];
            }),
            'transactionable_type' => $this->transactionable_type,
            'transactionable_id' => $this->transactionable_id,
            'transactionable' => $this->whenLoaded('transactionable', function () {
                $transactionable = $this->transactionable;
                return [
                    'id' => $transactionable->id,
                    'type' => class_basename($transactionable),
                    'name' => $this->getEntityName($transactionable),
                ];
            }),
            'type' => $this->type?->value ?? $this->type, // Handle both enum and string values
            'type_label' => $this->getTypeLabel(),
            'amount' => (float) $this->amount,
            'currency' => $this->currency ?? 'KWD',
            'currency_factor' => $this->currency_factor ?? 100,
            'payment_method' => $this->paymentMethod->name ?? 'N/A',
            'status' => $this->status->value ?? 'N/A',
            'status_label' => TransactionStatusEnum::label($this->status->value),
            'description' => $this->description,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Get the display name for the transactionable entity
     */
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

    /**
     * Get the label for the transaction type
     */
    private function getTypeLabel(): string
    {
        if ($this->type instanceof TransactionType) {
            return TransactionType::label($this->type->value);
        } elseif (is_string($this->type)) {
            return TransactionType::label($this->type);
        }

        return $this->type ?? 'Unknown';
    }
}
