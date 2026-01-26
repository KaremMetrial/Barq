<?php

namespace Modules\Promotion\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\PromotionTypeEnum;
use App\Enums\PromotionSubTypeEnum;
use App\Enums\PromotionTargetTypeEnum;

class CreatePromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:' . implode(',', PromotionTypeEnum::values())],
            'sub_type' => ['required', 'string', 'in:' . implode(',', PromotionSubTypeEnum::values())],
            'is_active' => ['boolean'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'usage_limit_per_user' => ['required', 'integer', 'min:1'],
            'current_usage' => ['integer', 'min:0'],
            'country_id' => ['nullable', 'exists:countries,id'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'zone_id' => ['nullable', 'exists:zones,id'],
            'min_order_amount' => ['nullable', 'integer', 'min:0'],
            'max_order_amount' => ['nullable', 'integer', 'min:0'],
            'discount_value' => ['nullable', 'integer', 'min:0'],
            'fixed_delivery_price' => ['nullable', 'integer', 'min:0'],
            'currency_factor' => ['integer', 'min:1'],
            'first_order_only' => ['boolean'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'targets' => ['array'],
            'targets.*.target_type' => ['required', 'string', 'in:' . implode(',', PromotionTargetTypeEnum::values())],
            'targets.*.target_id' => ['required', 'integer', 'min:1'],
            'targets.*.is_excluded' => ['boolean'],
            'fixed_prices' => ['array'],
            'fixed_prices.*.store_id' => ['nullable', 'exists:stores,id'],
            'fixed_prices.*.product_id' => ['nullable', 'exists:products,id'],
            'fixed_prices.*.fixed_price' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => __('validation.required', ['attribute' => __('promotion.type')]),
            'type.in' => __('validation.in', ['attribute' => __('promotion.type')]),
            'sub_type.required' => __('validation.required', ['attribute' => __('promotion.sub_type')]),
            'sub_type.in' => __('validation.in', ['attribute' => __('promotion.sub_type')]),
            'start_date.required' => __('validation.required', ['attribute' => __('promotion.start_date')]),
            'end_date.after_or_equal' => __('validation.after_or_equal', ['attribute' => __('promotion.end_date'), 'date' => __('promotion.start_date')]),
            'usage_limit_per_user.required' => __('validation.required', ['attribute' => __('promotion.usage_limit_per_user')]),
            'usage_limit_per_user.min' => __('validation.min.numeric', ['attribute' => __('promotion.usage_limit_per_user'), 'min' => 1]),
            'title.required' => __('validation.required', ['attribute' => __('promotion.title')]),
            'targets.*.target_type.in' => __('validation.in', ['attribute' => __('promotion.target_type')]),
            'fixed_prices.*.fixed_price.required' => __('validation.required', ['attribute' => __('promotion.fixed_price')]),
        ];
    }
}
