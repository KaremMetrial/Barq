<?php

namespace Modules\Reward\Http\Requests;

use App\Enums\RewardType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRewardRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpg,png,jpeg,gif,svg,webp', 'max:2048'],
            'type' => ['nullable', 'string', Rule::in(RewardType::values())],
            'points_cost' => ['nullable', 'integer', 'min:1'],
            'value_amount' => ['nullable', 'numeric', 'min:0'],
            'coupon_id' => ['nullable', 'integer', 'exists:coupons,id'],
            'country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'is_active' => ['nullable', 'boolean'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'usage_count' => ['nullable', 'integer', 'min:1'],
            'max_redemptions_per_user' => ['nullable', 'integer', 'min:1'],
            'is_it_for_loyalty_points' => ['nullable', 'boolean', 'required_if:type,' . RewardType::PRIZE->value],
            'is_it_for_spendings' => ['nullable', 'boolean', 'required_if:type,' . RewardType::PRIZE->value],
            'currency_factor' => ['nullable', 'numeric', 'min:0'],
            'resize' => ['nullable', 'array', 'min:2', 'max:2'],
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'end_date.after_or_equal' => 'The end date must be after or equal to the start date.',
        ];
    }
}
