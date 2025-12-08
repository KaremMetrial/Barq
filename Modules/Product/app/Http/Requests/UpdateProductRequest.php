<?php

namespace Modules\Product\Http\Requests;

use Illuminate\Validation\Rule;
use App\Enums\ProductStatusEnum;
use App\Enums\DeliveryTypeUnitEnum;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function prepareForValidation(): void
    {
        $product = $this->filterArray($this->input('product', []));
        if (auth('vendor')->check()) {
            $vendor = auth('vendor')->user();
            if ($vendor && $vendor->store_id) {
                $product['store_id'] = $vendor->store_id;
            } else {
                abort(422, 'Authenticated vendor does not have a store assigned.');
            }
        }

        $this->merge([
            'product' => $product,
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
        // Filter out null or empty values from the array
        return array_filter($data, fn($value) => !is_null($value) && $value !== '');
    }

    public function rules(): array
    {
        return [
            // Product Table
            'product' => ['required', 'array'],
            'product.barcode' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('products', 'barcode')->ignore($this->route('product'))
            ],
            'product.name' => ['required', 'string', 'max:255'],
            'product.description' => ['nullable', 'string'],
            'product.status' => ['nullable', 'string', Rule::in(ProductStatusEnum::values())],
            'product.note' => ['nullable', 'string'],
            'product.is_vegetarian' => ['nullable', 'boolean'],
            'product.is_reviewed' => ['nullable', 'boolean'],
            'product.is_featured' => ['nullable', 'boolean'],
            'product.is_active' => ['nullable', 'boolean'],
            'product.store_id' => ['required', 'integer', 'exists:stores,id'],
            'product.category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'product.max_cart_quantity' => ['nullable', 'integer', 'min:1'],
            'product.weight'             => ['nullable', 'numeric', 'min:0'],
            'product.preparation_time'   => ['nullable', 'integer', 'min:0'],
            'product.preparation_time_unit' => ['nullable', 'string', Rule::in(DeliveryTypeUnitEnum::values())],

            // Pharmacy Info
            'pharmacyInfo' => ['nullable', 'array'],
            'pharmacyInfo.*.generic_name' => ['required', 'string', 'max:255'],
            'pharmacyInfo.*.common_use' => ['required', 'string', 'max:255'],
            'pharmacyInfo.*.prescription_required' => ['required', 'boolean'],

            // Product Allergen
            'productAllergen' => ['nullable', 'array'],
            'productAllergen.*.name' => ['required', 'string', 'max:255'],

            // Availability
            'availability' => ['required', 'array'],
            'availability.stock_quantity' => ['required', 'integer', 'min:0'],
            'availability.is_in_stock' => ['required', 'boolean'],
            'availability.available_start_date' => ['nullable', 'date'],
            'availability.available_end_date' => ['nullable', 'date', 'after_or_equal:availability.available_start_date'],

            // Images
            'images' => ['nullable', 'array'],
            'images.*.image_path' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'images.*.is_primary' => ['nullable', 'boolean'],

            // Nutrition
            'productNutrition' => ['nullable', 'array'],
            'productNutrition.calories' => ['nullable', 'integer', 'min:0'],
            'productNutrition.fat' => ['nullable', 'integer'],
            'productNutrition.protein' => ['nullable', 'integer'],
            'productNutrition.carbohydrates' => ['nullable', 'integer'],
            'productNutrition.sugar' => ['nullable', 'integer'],
            'productNutrition.fiber' => ['nullable', 'integer'],

            // Prices
            'prices' => ['required', 'array'],
            'prices.price' => ['required', 'numeric', 'min:0'],
            'prices.purchase_price' => ['required', 'numeric', 'min:0'],

            // Tags
            'tags' => ['nullable', 'array'],
            'tags.*' => ['required', 'integer', 'exists:tags,id'],

            // Units
            'units' => ['nullable', 'array'],
            'units.*.unit_id' => ['required', 'integer', 'exists:units,id'],
            'units.*.unit_value' => ['required', 'numeric', 'min:0'],

            // Watermarks
            'watermarks' => ['nullable', 'array'],
            'watermarks.image_url' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'watermarks.position' => ['required_with:watermarks.image_url', 'string', 'max:255'],
            'watermarks.opacity' => ['required_with:watermarks.image_url', 'integer', 'min:0', 'max:100'],

            // Product Options + Values
            'productOptions' => ['nullable', 'array'],
            'productOptions.*.option_id' => ['required', 'integer', 'exists:options,id'],
            'productOptions.*.min_select' => ['nullable', 'integer', 'min:0'],
            'productOptions.*.max_select' => ['nullable', 'integer', 'min:1'],
            'productOptions.*.is_required' => ['nullable', 'boolean'],
            'productOptions.*.sort_order' => ['nullable', 'integer', 'min:1'],

            'productOptions.*.values' => ['nullable', 'array'],
            'productOptions.*.values.*.name' => ['nullable', 'string'],
            'productOptions.*.values.*.price_modifier' => ['nullable', 'numeric', 'min:0'],
            'productOptions.*.values.*.stock' => ['nullable', 'integer', 'min:0'],
            'productOptions.*.values.*.is_default' => ['nullable', 'boolean'],

            // Language
            'lang' => ['required', 'string', Rule::in(Cache::get('languages.codes'))],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Handle translation logic for fields that require language-specific keys.
     */
    protected function passedValidation(): void
    {
        $validated = $this->validated();

        $fieldTranslate = [
            'product' => ['name', 'description'],
            'pharmacyInfo' => ['generic_name', 'common_use'],
            'productAllergen' => ['name'],
            'productOptions' => ['name'],
        ];

        // Iterate over each section and translate the fields based on language
        foreach ($fieldTranslate as $section => $fields) {
            if (!isset($validated[$section], $validated['lang'])) {
                continue;
            }

            // Check if the section is an array and iterate accordingly
            if (array_is_list($validated[$section])) {
                foreach ($validated[$section] as $index => $item) {
                    foreach ($fields as $field) {
                        if (isset($item[$field])) {
                            $validated[$section][$index]["{$field}:{$validated['lang']}"] = $item[$field];
                            unset($validated[$section][$index][$field]);
                        }
                    }
                }
            } else {
                foreach ($fields as $field) {
                    if (isset($validated[$section][$field])) {
                        $validated[$section]["{$field}:{$validated['lang']}"] = $validated[$section][$field];
                        unset($validated[$section][$field]);
                    }
                }
            }
        }

        // Remove the language field after translation
        unset($validated['lang']);
        $this->replace($validated);
    }
}
