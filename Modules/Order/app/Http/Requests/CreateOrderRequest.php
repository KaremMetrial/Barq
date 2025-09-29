<?php

namespace Modules\Order\Http\Requests;

use App\Enums\OrderTypeEnum;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
{
    public function prepareForValidation()
    {
        $this->merge([
            "order" => $this->filterArray($this->input("order", [])),
            "items" => $this->filterArray($this->input("items", [])),
        ]);
    }
    private function filterArray(array $data): array
    {
        return array_filter($data, function ($value) {
            return !is_null($value) && $value !== '';
        });
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // order
            "order" => ["required", "array"],
            "order.type" => ["required", Rule::in(OrderTypeEnum::values())],
            "order.note" => ["nullable", "string"],
            "order.requires_otp" => ["nullable", "boolean"],
            "order.delivery_address" => ["nullable", "string"],

            // order items
            "items" => ["required", "array"],
            "items.*.product_id" => ["required", "integer", "exists:products,id"],
            "items.*.quantity" => ["required", "integer", "min:1"],
            "items.*.option_id" => ["nullable", "integer"],
            "items.*.product_option_value_id" => ["nullable", "integer"],

            // add ons
            "items.*.add_ons" => ["nullable", "array"],
            "items.*.add_ons.*.add_on_id" => ["required", "integer", "exists:add_ons,id"],
            "items.*.add_ons.*.quantity" => ["required", "integer", "min:1"],
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
