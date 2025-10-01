<?php

namespace Modules\Cart\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCartRequest extends FormRequest
{
    public function prepareForValidation()
    {
        $this->merge([
            'cart' => $this->filterArray($this->input('cart', [])),
            'items' => $this->filterArray($this->input('items', [])),
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
            // Cart Table
            "cart" => ["nullable", "array"],
            "cart.pos_shift_id" => ["nullable", "integer", "exists:pos_shifts,id"],
            "cart.store_id" => ["nullable", "integer", "exists:stores,id"],
            "cart.user_id" => ["nullable", "integer", "exists:users,id"],

            // Cart Item Table
            "items" => ["required", "array"],
            "items.*.product_id" => ["required", "integer", "exists:products,id"],
            "items.*.quantity" => ["required", "integer", "min:0"],
            "items.*.note" => ["nullable", "string"],
            "items.*.product_option_value_id" => ["nullable", "integer", "exists:product_option_values,id"],
            "items.*.total_price" => ["nullable", "numeric", "min:0"],

            // Add On Pivot Table
            "items.*.add_ons" => ["nullable", "array"],
            "items.*.add_ons.*.id" => ["required_with:items.*.add_ons", "integer", "exists:add_ons,id"],
            "items.*.add_ons.*.quantity" => ["required_with:items.*.add_ons", "integer", "min:1"],
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
    protected function passedValidation(): void
    {
        $validated = $this->validated();

        $validated = array_filter($validated, fn($value) => !blank($value));

        $this->replace($validated);
    }
}
