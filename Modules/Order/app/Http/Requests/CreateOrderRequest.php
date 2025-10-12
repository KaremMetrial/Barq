<?php

namespace Modules\Order\Http\Requests;

use App\Enums\OrderTypeEnum;
use App\Enums\ProductStatusEnum;
use App\Enums\StoreStatusEnum;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductOptionValue;
use Modules\AddOn\Models\AddOn;
use Modules\Store\Models\Store;

class CreateOrderRequest extends FormRequest
{
    public function prepareForValidation()
    {
        $this->merge([
            "order" => $this->filterArray($this->input("order", [])),
            "items" => $this->filterArray($this->input("items", [])),
            "address" => $this->filterArray($this->input("address", [])),
        ]);
    }

    private function filterArray(array $data): array
    {
        return array_filter($data, function ($value) {
            if (is_array($value)) {
                return !empty($this->filterArray($value));
            }
            return !is_null($value) && $value !== '';
        });
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Order data
            "order" => ["required", "array"],
            "order.store_id" => [
                "required",
                "integer",
                "exists:stores,id",
                function ($attribute, $value, $fail) {
                    $store = Store::find($value);
                    if (!$store) {
                        $fail('The selected store does not exist.');
                    } elseif (!$store->is_active || $store->status != StoreStatusEnum::APPROVED) {
                        $fail('The selected store is not available.');
                    } elseif ($store->is_closed) {
                        $fail('The selected store is currently closed.');
                    }
                }
            ],
            "order.type" => ["required", Rule::in(OrderTypeEnum::values())],
            "order.note" => ["nullable", "string", "max:1000"],
            "order.requires_otp" => ["nullable", "boolean"],
            "order.delivery_address" => [
                "nullable",
                "string",
                "max:500",
                "required_if:order.type,delivery"
            ],
            "order.tip_amount" => ["nullable", "numeric", "min:0", "max:1000"],
            "order.coupon_code" => [
                "nullable",
                "string",
                "exists:coupons,code"
            ],

            // Order items
            "items" => ["required", "array", "min:1"],
            "items.*.product_id" => [
                "required",
                "integer",
                "exists:products,id",
                function ($attribute, $value, $fail) {
                    $product = Product::with('availability')->find($value);
                    if (!$product) {
                        $fail('The selected product does not exist.');
                    } elseif (!$product->is_active || $product->status != ProductStatusEnum::ACTIVE) {
                        $fail('The selected product is not available.');
                    } elseif ($product->availability && !$product->availability->is_in_stock) {
                        $fail('The selected product is out of stock.');
                    }
                }
            ],
            "items.*.quantity" => [
                "required",
                "integer",
                "min:1",
                function ($attribute, $value, $fail) {
                    // Extract item index
                    preg_match('/items\.(\d+)\.quantity/', $attribute, $matches);
                    $index = $matches[1] ?? null;

                    if ($index !== null && isset($this->input('items')[$index])) {
                        $item = $this->input('items')[$index];
                        $product = Product::with('availability')->find($item['product_id'] ?? null);

                        if ($product) {
                            // Check max cart quantity
                            if ($value > $product->max_cart_quantity) {
                                $fail("Maximum quantity for this product is {$product->max_cart_quantity}.");
                            }

                            // Check stock availability
                            if ($product->availability && $value > $product->availability->stock_quantity) {
                                $fail("Only {$product->availability->stock_quantity} items available in stock.");
                            }
                        }
                    }
                }
            ],
            "items.*.product_option_value_id" => [
                "nullable",
                "integer",
                "exists:product_option_values,id",
                function ($attribute, $value, $fail) {
                    if (!$value) return;

                    preg_match('/items\.(\d+)\./', $attribute, $matches);
                    $index = $matches[1] ?? null;

                    if ($index !== null && isset($this->input('items')[$index])) {
                        $item = $this->input('items')[$index];
                        $productId = $item['product_id'] ?? null;

                        if ($productId) {
                            $optionValue = ProductOptionValue::whereHas('productOption', function($q) use ($productId) {
                                $q->where('product_id', $productId);
                            })->find($value);
                            if (!$optionValue) {
                                $fail('The selected option does not belong to this product.');
                            }
                        }
                    }
                }
            ],
            "items.*.note" => ["nullable", "string", "max:500"],

            // Add-ons validation
            "items.*.add_ons" => ["nullable", "array"],
            "items.*.add_ons.*.id" => [
                "required",
                "integer",
                "exists:add_ons,id",
                function ($attribute, $value, $fail) {
                    $addOn = AddOn::find($value);
                    if ($addOn && !$addOn->is_active) {
                        $fail('The selected add-on is not available.');
                    }

                    // Validate add-on belongs to product
                    preg_match('/items\.(\d+)\./', $attribute, $matches);
                    $index = $matches[1] ?? null;

                    if ($index !== null && isset($this->input('items')[$index])) {
                        $item = $this->input('items')[$index];
                        $productId = $item['product_id'] ?? null;

                        if ($productId && $addOn) {
                            if ($addOn->applicable_to === 'product') {
                                $hasAddOn = $addOn->products()->where('product_id', $productId)->exists();
                                if (!$hasAddOn) {
                                    $fail('This add-on is not available for the selected product.');
                                }
                            }
                        }
                    }
                }
            ],
            "items.*.add_ons.*.quantity" => ["required", "integer", "min:1", "max:10"],
            "items.*.add_ons.*.price" => ["required", "numeric", "min:0"],

            // Address validation (for delivery orders)
            "address" => [
                "nullable",
                "array"
            ],
            "address.latitude" => [
                "required_with:address",
                "string"
            ],
            "address.longitude" => [
                "required_with:address",
                "string"
            ],
            "address.name" => ["nullable", "string", "max:255"],
            "address.phone" => ["nullable", "string", "max:20"],
            "address.type" => [
                "nullable",
                "string",
                Rule::in(['home', 'work', 'other'])
            ],
            "address.zone_id" => ["nullable", "integer", "exists:zones,id"],
            "address.address_line_1" => ["required_with:address", "string", "max:255"],
            "address.address_line_2" => ["nullable", "string", "max:255"],
        ];
    }

    /**
     * Custom validation messages
     */
    public function messages(): array
    {
        return [
            "order.required" => "Order data is required.",
            "order.store_id.required" => "Store selection is required.",
            "order.store_id.exists" => "The selected store does not exist.",
            "order.type.required" => "Order type is required.",
            "order.type.in" => "Invalid order type selected.",
            "order.delivery_address.required_if" => "Delivery address is required for delivery orders.",
            "order.coupon_code.exists" => "Invalid coupon code.",

            "items.required" => "At least one item is required.",
            "items.*.product_id.required" => "Product is required for each item.",
            "items.*.product_id.exists" => "One or more selected products do not exist.",
            "items.*.quantity.required" => "Quantity is required for each item.",
            "items.*.quantity.min" => "Quantity must be at least 1.",
            "items.*.product_option_value_id.exists" => "Invalid product option selected.",

            "items.*.add_ons.*.id.required" => "Add-on ID is required.",
            "items.*.add_ons.*.id.exists" => "Invalid add-on selected.",
            "items.*.add_ons.*.quantity.required" => "Add-on quantity is required.",
            "items.*.add_ons.*.quantity.min" => "Add-on quantity must be at least 1.",
            "items.*.add_ons.*.quantity.max" => "Add-on quantity cannot exceed 10.",
            "items.*.add_ons.*.price.required" => "Add-on price is required.",

            "address.required_if" => "Delivery address is required for delivery orders.",
            "address.latitude.required_with" => "Latitude is required when providing address.",
            "address.longitude.required_with" => "Longitude is required when providing address.",
            "address.latitude.between" => "Invalid latitude value.",
            "address.longitude.between" => "Invalid longitude value.",
            "address.address_line_1.required_with" => "Address line 1 is required.",
        ];
    }

    /**
     * Custom attributes for error messages
     */
    public function attributes(): array
    {
        return [
            "order.store_id" => "store",
            "order.type" => "order type",
            "order.delivery_address" => "delivery address",
            "order.coupon_code" => "coupon code",
            "items.*.product_id" => "product",
            "items.*.quantity" => "quantity",
            "items.*.product_option_value_id" => "product option",
            "items.*.add_ons.*.id" => "add-on",
            "items.*.add_ons.*.quantity" => "add-on quantity",
            "address.latitude" => "latitude",
            "address.longitude" => "longitude",
            "address.address_line_1" => "address",
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        throw new \Illuminate\Validation\ValidationException(
            $validator,
            response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422)
        );
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::guard('user')->check();
    }

    /**
     * Get validated data with proper structure
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);

        // Ensure proper structure
        if (!$key) {
            return [
                'order' => $validated['order'] ?? [],
                'items' => $validated['items'] ?? [],
                'address' => $validated['address'] ?? null,
            ];
        }

        return $validated;
    }
}
