<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\PromotionTypeEnum;
use App\Enums\PromotionSubTypeEnum;
use App\Enums\PromotionTargetTypeEnum;

class UpdatePromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['sometimes', 'string', 'in:' . implode(',', PromotionTypeEnum::values())],
            'sub_type' => ['sometimes', 'string', 'in:' . implode(',', PromotionSubTypeEnum::values())],
            'is_active' => ['boolean'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'usage_limit_per_user' => ['sometimes', 'integer', 'min:1'],
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
            'translations' => ['sometimes', 'array'],
            'translations.*.locale' => ['required_with:translations', 'string', 'in:ar,en'],
            'translations.*.title' => ['required_with:translations', 'string', 'max:255'],
            'translations.*.description' => ['nullable', 'string'],
            'targets' => ['sometimes', 'array'],
            'targets.*.target_type' => ['required_with:targets', 'string', 'in:' . implode(',', PromotionTargetTypeEnum::values())],
            'targets.*.target_id' => ['required_with:targets', 'integer', 'min:1'],
            'targets.*.is_excluded' => ['boolean'],
            'fixed_prices' => ['sometimes', 'array'],
            'fixed_prices.*.store_id' => ['nullable', 'exists:stores,id'],
            'fixed_prices.*.product_id' => ['nullable', 'exists:products,id'],
            'fixed_prices.*.fixed_price' => ['required_with:fixed_prices', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.in' => __('validation.in', ['attribute' => __('promotion.type')]),
            'sub_type.in' => __('validation.in', ['attribute' => __('promotion.sub_type')]),
            'end_date.after_or_equal' => __('validation.after_or_equal', ['attribute' => __('promotion.end_date'), 'date' => __('promotion.start_date')]),
            'usage_limit_per_user.min' => __('validation.min.numeric', ['attribute' => __('promotion.usage_limit_per_user'), 'min' => 1]),
            'translations.*.locale.in' => __('validation.in', ['attribute' => __('promotion.locale')]),
            'translations.*.title.required_with' => __('validation.required', ['attribute' => __('promotion.title')]),
            'targets.*.target_type.in' => __('validation.in', ['attribute' => __('promotion.target_type')]),
            'targets.*.target_id.required_with' => __('validation.required', ['attribute' => __('promotion.target_id')]),
            'fixed_prices.*.fixed_price.required_with' => __('validation.required', ['attribute' => __('promotion.fixed_price')]),
        ];
    }
}