<?php

namespace Modules\Coupon\Http\Requests;

use App\Enums\SaleTypeEnum;
use App\Enums\CouponTypeEnum;
use App\Enums\ObjectTypeEnum;
use Illuminate\Validation\Rule;
use Modules\Store\Models\Store;
use Illuminate\Foundation\Http\FormRequest;

class CreateCouponRequest extends FormRequest
{
    protected function prepareForValidation()
    {
        if (auth('vendor')->check()) {
            $this->merge([
                'store_ids' => [auth('vendor')->user()->store_id],
            ]);
        }
    }
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255', 'unique:coupons,code'],
            'discount_amount' => ['required', 'numeric', 'min:0'],
            'discount_type' => ['required', Rule::in(SaleTypeEnum::values())],
            'usage_limit' => ['nullable', 'numeric', 'min:0'],
            'usage_limit_per_user' => ['nullable', 'integer', 'min:1'],
            'minimum_order_amount' => ['nullable', 'integer', 'min:1'],
            'maximum_order_amount' => ['nullable', 'integer', 'min:1'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'is_active' => ['nullable', 'boolean'],
            'coupon_type' => ['required', Rule::in(CouponTypeEnum::values())],
            'object_type' => ['required', Rule::in(ObjectTypeEnum::values())],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['integer', 'exists:products,id'],
            'store_ids' => ['nullable', 'array'],
            'store_ids.*' => ['integer', 'exists:stores,id'],
            'currency_factor' => [
                'nullable',
                'integer',
                'min:1',
                function ($attribute, $value, $fail) {
                    $storeIds = $this->input('store_ids');

                    if (!empty($storeIds)) {
                        $stores = Store::whereIn('id', $storeIds)->get();
                        if ($stores->isEmpty()) {
                            return;
                        }

                        $firstStoreCurrencyFactor = $stores->first()->getCurrencyFactor();

                        foreach ($stores as $store) {
                            if ($store->getCurrencyFactor() !== $firstStoreCurrencyFactor) {
                                $fail('All selected stores must have the same currency factor.');
                                return;
                            }
                        }

                        if ($value != $firstStoreCurrencyFactor) {
                            $fail("The currency factor must match the selected stores' currency factor of {$firstStoreCurrencyFactor}.");
                        }
                    }
                },
            ],
            'country_id' => ['required', 'integer', 'exists:countries,id'],
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
