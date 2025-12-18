<?php

namespace Modules\Coupon\Http\Requests;

use App\Enums\SaleTypeEnum;
use App\Enums\CouponTypeEnum;
use App\Enums\ObjectTypeEnum;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Coupon\Models\Coupon;

class UpdateCouponRequest extends FormRequest
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
            'code' => ['nullable', 'string', 'max:255', 'unique:coupons,code,' . $this->route('coupon')],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'discount_type' => ['nullable', Rule::in(SaleTypeEnum::values())],
            'usage_limit' => ['nullable', 'numeric', 'min:0'],
            'usage_limit_per_user' => ['nullable', 'integer', 'min:1'],
            'minimum_order_amount' => ['nullable', 'integer', 'min:1'],
            'start_date' => ['nullable', 'date','after_or_equal:today'],
            'end_date' => ['nullable', 'date','after:start_date'],
            'is_active' => ['nullable', 'boolean'],
            'coupon_type' => ['nullable', Rule::in(CouponTypeEnum::values())],
            'object_type' => ['nullable', Rule::in(ObjectTypeEnum::values())],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['integer', 'exists:products,id'],
            'store_ids' => ['nullable', 'array'],
            'store_ids.*' => ['integer', 'exists:stores,id'],
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
