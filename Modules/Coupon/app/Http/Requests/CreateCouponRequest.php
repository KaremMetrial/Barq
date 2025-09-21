<?php

namespace Modules\Coupon\Http\Requests;

use App\Enums\SaleTypeEnum;
use App\Enums\CouponTypeEnum;
use App\Enums\ObjectTypeEnum;
use App\Enums\SectionTypeEnum;
use Illuminate\Validation\Rule;
use Modules\Section\Models\Section;
use Illuminate\Foundation\Http\FormRequest;

class CreateCouponRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:255', 'unique:coupons,code'],
            'discount_amount' => ['required', 'numeric', 'min:0'],
            'discount_type' => ['required', Rule::in(SaleTypeEnum::values())],
            'usage_limit' => ['nullable', 'numeric', 'min:0'],
            'usage_limit_per_user' => ['nullable', 'integer', 'min:1'],
            'minimum_order_amount' => ['nullable', 'integer', 'min:1'],
            'start_date' => ['required', 'date','after_or_equal:today'],
            'end_date' => ['required', 'date','after:start_date'],
            'is_active' => ['nullable', 'boolean'],
            'coupon_type' => ['required', Rule::in(CouponTypeEnum::values())],
            'object_type' => ['required', Rule::in(ObjectTypeEnum::values())],
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
