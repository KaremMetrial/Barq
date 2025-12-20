<?php

namespace Modules\Product\Http\Requests;

use App\Enums\SaleTypeEnum;
use App\Enums\ProductTypeEnum;
use Illuminate\Validation\Rule;
use App\Enums\ProductStatusEnum;
use App\Enums\DeliveryTypeUnitEnum;
use Modules\Product\Models\Product;
use Illuminate\Foundation\Http\FormRequest;

class CreateProductRequest extends FormRequest
{
    public function prepareForValidation()
    {
        $product = $this->filterArray($this->input('product', []));
        if (auth('vendor')->check()) {
            $vendor = auth('vendor')->user();
            $product['store_id'] = $vendor->store_id;
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
            'add_ons' => $this->filterArray($this->input('add_ons', [])),
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
            // Product Table
            'product' => ['required', 'array'],
            'product.barcode'            => ['nullable', 'string', 'max:255', 'unique:products,barcode'],
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
            'product.max_cart_quantity'  => ['nullable', 'integer', 'min:1', 'max:99'],
            'product.weight'             => ['nullable', 'numeric', 'min:0'],
            'product.preparation_time'   => ['nullable', 'integer', 'min:0'],
            'product.preparation_time_unit' => ['nullable', 'string', Rule::in(DeliveryTypeUnitEnum::values())],


            // Parmacy Info Table
            'pharmacyInfo' => ['nullable', 'array'],
            'pharmacyInfo.*.generic_name' => ['required', 'string', 'max:255'],
            'pharmacyInfo.*.common_use' => ['required', 'string', 'max:255'],
            'pharmacyInfo.*.prescription_required' => ['required', 'boolean'],

            // Product AlLergen Table
            'productAllergen' => ['nullable', 'array'],
            'productAllergen.*.name' => ['required', 'string', 'max:255'],

            // Produc Availability Table
            'availability' => ['required', 'array'],
            'availability.stock_quantity' => ['required', 'integer', 'min:0'],
            'availability.is_in_stock'   => ['required', 'boolean'],
            'availability.available_start_date' => ['nullable', 'date'],
            'availability.available_end_date' => ['nullable', 'date', 'after_or_equal:availability.available_start_date'],

            // Product Image Table
            'images' => ['required', 'array'],
            'images.*.image_path' => ['required', 'image', 'mimes:jpeg,png,jpg,gif'],
            'images.*.is_primary' => ['nullable', 'boolean'],


            // Product Nutrition table
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
            'prices.purchase_price' => ['nullable', 'numeric', 'min:0'],
            'prices.sale_price' => ['nullable', 'numeric', 'min:0'],
            'prices.currency_factor' => ['nullable', 'integer', 'min:1'],

            // Product Tags Table
            'tags' => ['nullable', 'array'],
            'tags.*' => ['required', 'integer', 'exists:tags,id'],

            // Product Units Table
            'units' => ['nullable', 'array'],
            'units.*.unit_id' => ['required', 'integer', 'exists:units,id'],
            'units.*.unit_value' => ['required', 'numeric', 'min:0'],

            // Product Watermarks Table
            'watermarks' => ['nullable', 'array'],
            'watermarks.image_url' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif'],
            'watermarks.position' => ['nullable', 'string', 'max:255'],
            'watermarks.opacity' => ['nullable', 'integer', 'min:0', 'max:100'],

            // Product Option and Values Table
            'productOptions' => ['nullable', 'array'],
            'productOptions.*.option_id' => ['required', 'integer', 'exists:options,id'],
            'productOptions.*.min_select' => ['nullable', 'integer', 'min:0'],
            'productOptions.*.max_select' => ['nullable', 'integer', 'min:1'],
            'productOptions.*.is_required' => ['nullable', 'boolean'],
            'productOptions.*.sort_order' => ['nullable', 'integer', 'min:1'],

            'productOptions.*.values' => ['nullable', 'array'],
            'productOptions.*.values.*.name' => ['required', 'string', 'max:255'],
            'productOptions.*.values.*.price' => ['nullable', 'numeric', 'min:0'],
            'productOptions.*.values.*.stock' => ['nullable', 'integer', 'min:0'],
            'productOptions.*.values.*.is_default' => ['nullable', 'boolean'],

            'add_ons' => ['nullable', 'array'],
            'add_ons.*' => ['required', 'integer', 'exists:add_ons,id'],
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
