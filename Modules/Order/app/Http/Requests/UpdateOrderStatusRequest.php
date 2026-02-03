<?php

namespace Modules\Order\Http\Requests;

use App\Enums\OrderStatus;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderStatusRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(OrderStatus::values())],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $orderId = $this->route('id');
            $newStatus = $this->input('status');

            if (!$this->isValidStatusTransition($orderId, $newStatus)) {
                $order = \Modules\Order\Models\Order::find($orderId);
                $currentStatus = $order ? $order->status->value : 'unknown';
                $allowedTransitions = $this->getAllowedTransitions($currentStatus);
                $validator->errors()->add('status', __('message.invalid_status_transition', [
                    'current' => $currentStatus,
                    'allowed' => implode(', ', $allowedTransitions)
                ]));
            }
        });
    }

    /**
     * Validate status transition logic
     */
    private function isValidStatusTransition($orderId, $newStatus): bool
    {
        $order = \Modules\Order\Models\Order::find($orderId);

        if (!$order) {
            return false;
        }

        $currentStatus = $order->status->value;

        // Business rules: Cannot cancel or change status after delivery
        if ($currentStatus === OrderStatus::DELIVERED->value) {
            return false; // No transitions allowed from delivered
        }

        // Cannot cancel after certain stages (e.g., after on the way)
        if (
            $newStatus === OrderStatus::CANCELLED->value &&
            in_array($currentStatus, [OrderStatus::ON_THE_WAY->value, OrderStatus::DELIVERED->value])
        ) {
            return false;
        }

        // Define valid transitions
        $validTransitions = [
            OrderStatus::PENDING->value => [
                OrderStatus::CONFIRMED->value,
                OrderStatus::CANCELLED->value,
            ],
            OrderStatus::CONFIRMED->value => [
                OrderStatus::PROCESSING->value,
                OrderStatus::CANCELLED->value,
            ],
            OrderStatus::PROCESSING->value => [
                OrderStatus::READY_FOR_DELIVERY->value,
                OrderStatus::CANCELLED->value,
            ],
            OrderStatus::READY_FOR_DELIVERY->value => [
                OrderStatus::ON_THE_WAY->value,
                OrderStatus::CANCELLED->value,
            ],
            OrderStatus::ON_THE_WAY->value => [
                OrderStatus::DELIVERED->value,
            ],
            OrderStatus::DELIVERED->value => [], // Final state - no transitions
            OrderStatus::CANCELLED->value => [], // Final state - no transitions
        ];
        return in_array($newStatus, $validTransitions[$currentStatus] ?? []);
    }

    /**
     * Get allowed transitions for a given status
     */
    private function getAllowedTransitions($currentStatus): array
    {
        $validTransitions = [
            OrderStatus::PENDING->value => [
                OrderStatus::CONFIRMED->value,
                OrderStatus::CANCELLED->value,
            ],
            OrderStatus::CONFIRMED->value => [
                OrderStatus::PROCESSING->value,
                OrderStatus::CANCELLED->value,
            ],
            OrderStatus::PROCESSING->value => [
                OrderStatus::READY_FOR_DELIVERY->value,
                OrderStatus::CANCELLED->value,
            ],
            OrderStatus::READY_FOR_DELIVERY->value => [
                OrderStatus::ON_THE_WAY->value,
                OrderStatus::CANCELLED->value,
            ],
            OrderStatus::ON_THE_WAY->value => [
                OrderStatus::DELIVERED->value,
            ],
            OrderStatus::DELIVERED->value => [], // Final state - no transitions
            OrderStatus::CANCELLED->value => [], // Final state - no transitions
        ];

        return $validTransitions[$currentStatus] ?? [];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
