<?php

namespace Modules\Cart\Http\Requests;

use App\Traits\FileUploadTrait;
use Illuminate\Validation\Rule;
use Modules\Cart\Models\Cart;
use Illuminate\Foundation\Http\FormRequest;

class CreateCartRequest extends FormRequest
{
    public function prepareForValidation()
    {
        $this->merge([
            'product' => $this->filterArray($this->input('product', [])),
            'pharmacyInfo' => $this->filterArray($this->input('pharmacyInfo', [])),
            'productAllergen' => $this->filterArray($this->input('productAllergen', [])),
            'availability' => $this->filterArray($this->input('availability', [])),
            'productNutrition' => $this->filterArray($this->input('productNutrition', [])),
            'prices' => $this->filterArray($this->input('prices', [])),
            'tags' => $this->filterArray($this->input('tags', [])),
            'units' => $this->filterArray($this->input('units', [])),
            'watermarks' => $this->filterArray($this->input('watermarks', [])),
            'productOptions' => $this->filterArray($this->input('productOptions', [])),
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
            "items.*.quantity" => ["required", "integer", "min:1"],
            "items.*.note" => ["nullable", "string"],
            "items.*.product_option_value_id" => ["nullable", "integer", "exists:product_option_values,id"],

            // Add On Pivot Table
            // "items.*.add_ons" => ["nullable", "array"],
            // "items.*.add_ons.*.id" => ["required_with:items.*.add_ons", "integer", "exists:add_ons,id"],
            // "items.*.add_ons.*.quantity" => ["required_with:items.*.add_ons", "integer", "min:1"],
            // "items.*.add_ons.*.price" => ["required_with:items.*.add_ons", "numeric", "min:0"],
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
