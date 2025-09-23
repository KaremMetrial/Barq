<?php

namespace Modules\Product\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\ProductStatusEnum;

class UpdateProductRequest extends FormRequest
{
    public function prepareForValidation()
    {
        $this->merge([
            'product' => $this->filterArray($this->input('product', [])),
            'pharmacyInfo' => $this->filterArray($this->input('pharmacyInfo', [])),
            'productAllergen' => $this->filterArray($this->input('productAllergen', [])),
            'availability' => $this->filterArray($this->input('availability', [])),
        ]);
    }

    private function filterArray(array $data): array
    {
        return array_filter($data, fn($value) => !is_null($value) && $value !== '');
    }

    public function rules(): array
    {
        return [
            // Product Table
            'product' => ['required', 'array'],
            'product.barcode'            => ['nullable', 'string', 'max:255', Rule::unique('products', 'barcode')->ignore($this->route('id'))],
            'product.name'               => ['required', 'string', 'max:255'],
            'product.description'        => ['nullable', 'string'],
            'product.status'             => ['nullable', 'string', Rule::in(ProductStatusEnum::values())],
            'product.note'               => ['nullable', 'string'],
            'product.is_vegetarian'      => ['nullable', 'boolean'],
            'product.is_reviewed'        => ['nullable', 'boolean'],
            'product.is_featured'        => ['nullable', 'boolean'],
            'product.is_active'          => ['nullable', 'boolean'],
            'product.store_id'           => ['required', 'integer', 'exists:stores,id'],
            'product.category_id'        => ['nullable', 'integer', 'exists:categories,id'],
            'product.max_cart_quantity'  => ['nullable', 'integer', 'min:1'],

            // Pharmacy Info Table
            'pharmacyInfo' => ['nullable', 'array'],
            'pharmacyInfo.*.generic_name' => ['required', 'string', 'max:255'],
            'pharmacyInfo.*.common_use' => ['required', 'string', 'max:255'],
            'pharmacyInfo.*.prescription_required' => ['required', 'boolean'],

            // Product Allergen Table
            'productAllergen' => ['nullable', 'array'],
            'productAllergen.*.name' => ['required', 'string', 'max:255'],

            // Product Availability Table
            'availability' => ['required', 'array'],
            'availability.stock_quantity' => ['required', 'integer', 'min:0'],
            'availability.is_in_stock'   => ['required', 'boolean'],
            'availability.available_start_date' => ['nullable', 'date'],
            'availability.available_end_date' => ['nullable', 'date', 'after_or_equal:availability.available_start_date'],

            // Product Image Table (optional on update)
            'images' => ['nullable', 'array'],
            'images.*.image_path' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'images.*.is_primary' => ['nullable', 'boolean'],

            // Product Nutrition Table
            'productNutrition' => ['nullable', 'array'],
            'productNutrition.calories' => ['nullable', 'integer', 'min:0'],
            'productNutrition.fat' => ['nullable', 'integer'],
            'productNutrition.protein' => ['nullable', 'integer'],
            'productNutrition.carbohydrates' => ['nullable', 'integer'],
            'productNutrition.sugar' => ['nullable', 'integer'],
            'productNutrition.fiber' => ['nullable', 'integer'],

            // Product Price Table
            'prices' => ['required', 'array'],
            'prices.price' => ['required', 'numeric', 'min:0'],
            'prices.purchase_price' => ['required', 'numeric', 'min:0'],

            // Product Tags Table
            'tags' => ['nullable', 'array'],
            'tags.*'=> ['required', 'integer','exists:tags,id'],

            // Product Units Table (with pivot data)
            'units' => ['nullable', 'array'],
            'units.*.unit_id' => ['required', 'integer', 'exists:units,id'],
            'units.*.unit_value' => ['required', 'numeric', 'min:0'],

            // Product Watermark Table
            'watermarks' => ['nullable', 'array'],
            'watermarks.image_url' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'watermarks.position' => ['required_with:watermarks.image_url', 'string', 'max:255'],
            'watermarks.opacity' => ['required_with:watermarks.image_url', 'integer', 'min:0', 'max:100'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
