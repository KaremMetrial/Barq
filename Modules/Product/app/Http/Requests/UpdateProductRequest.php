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
        $this->merge(input: [
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
            'add_ons' => $this->filterArray($this->input('add_ons', [])),
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
            'product' => ['nullable', 'array'],
            'product.barcode' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('products', 'barcode')->ignore($this->route('product'))
            ],
            'product.name' => ['nullable', 'string', 'max:255'],
            'product.description' => ['nullable', 'string'],
            'product.status' => ['nullable', 'string', Rule::in(ProductStatusEnum::values())],
            'product.note' => ['nullable', 'string'],
            'product.is_vegetarian' => ['nullable', 'boolean'],
            'product.is_reviewed' => ['nullable', 'boolean'],
            'product.is_featured' => ['nullable', 'boolean'],
            'product.is_active' => ['nullable', 'boolean'],
            // 'product.store_id' => ['nullable', 'integer', 'exists:stores,id'],
            'product.category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'product.max_cart_quantity' => ['nullable', 'integer', 'min:1'],
            'product.weight'             => ['nullable', 'numeric', 'min:0'],
            'product.preparation_time'   => ['nullable', 'integer', 'min:0'],
            'product.preparation_time_unit' => ['nullable', 'string', Rule::in(DeliveryTypeUnitEnum::values())],

            // Pharmacy Info
            'pharmacyInfo' => ['nullable', 'array'],
            'pharmacyInfo.*.generic_name' => ['nullable', 'string', 'max:255'],
            'pharmacyInfo.*.common_use' => ['nullable', 'string', 'max:255'],
            'pharmacyInfo.*.prescription_nullable' => ['nullable', 'boolean'],

            // Product Allergen
            'productAllergen' => ['nullable', 'array'],
            'productAllergen.*.name' => ['nullable', 'string', 'max:255'],

            // Availability
            'availability' => ['nullable', 'array'],
            'availability.stock_quantity' => ['nullable', 'integer', 'min:0'],
            'availability.is_in_stock' => ['nullable', 'boolean'],
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
            'prices' => ['nullable', 'array'],
            'prices.price' => ['nullable', 'numeric', 'min:0'],
            'prices.purchase_price' => ['nullable', 'numeric', 'min:0'],
            'prices.sale_price' => ['nullable', 'numeric', 'min:0', 'lt:prices.price'],
            'prices.currency_factor' => ['nullable', 'integer', 'min:1'],

            // Tags
            'tags' => ['nullable', 'array'],
            'tags.*' => ['nullable', 'integer', 'exists:tags,id'],

            // Units
            'units' => ['nullable', 'array'],
            'units.*.unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'units.*.unit_value' => ['nullable', 'numeric', 'min:0'],

            // Watermarks
            'watermarks' => ['nullable', 'array'],
            'watermarks.image_url' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'watermarks.position' => ['nullable_with:watermarks.image_url', 'string', 'max:255'],
            'watermarks.opacity' => ['nullable_with:watermarks.image_url', 'integer', 'min:0', 'max:100'],

            // Product Options + Values
            'productOptions' => ['nullable', 'array'],
            'productOptions.*.option_id' => ['nullable', 'integer', 'exists:options,id'],
            'productOptions.*.min_select' => ['nullable', 'integer', 'min:0'],
            'productOptions.*.max_select' => ['nullable', 'integer', 'min:1'],
            'productOptions.*.is_nullable' => ['nullable', 'boolean'],
            'productOptions.*.sort_order' => ['nullable', 'integer', 'min:1'],

            'productOptions.*.values' => ['nullable', 'array'],
            'productOptions.*.values.*.name' => ['nullable', 'string'],
            'productOptions.*.values.*.price_modifier' => ['nullable', 'numeric', 'min:0'],
            'productOptions.*.values.*.stock' => ['nullable', 'integer', 'min:0'],
            'productOptions.*.values.*.is_default' => ['nullable', 'boolean'],

            // Add Ons
            'add_ons' => ['nullable', 'array'],
            'add_ons.*' => ['nullable', 'integer', 'exists:add_ons,id'],

            // Language
            'lang' => ['nullable', 'string', Rule::in(Cache::get('languages.codes'))],
        ];
    }

    public function authorize(): bool
    {
        // $user = auth('admin')->user();
        // if ($user) {
        //     return true;
        // }

        // $vendor = auth('vendor')->user();
        // if ($vendor) {
        //     $product = $this->route('product');
        //     return $product && $vendor->store_id && $vendor->store_id == $product->store_id;
        // }

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
