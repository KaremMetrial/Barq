<?php

namespace Modules\Reward\Http\Requests;

use App\Enums\RewardType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Country\Services\CountryService;

class CreateRewardRequest extends FormRequest
{
    public function prepareForValidation()
    {
        $countryService = app(CountryService::class);
        $currencyFactor = $this->input('currency_factor') ?? $countryService->getCountryById((int)$this->country_id)->currency_factor ?? 1;
        $this->merge([
            'currency_factor' => $currencyFactor,
        ]);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'required_if:type,prize', 'image', 'mimes:jpg,png,jpeg,gif,svg,webp', 'max:2048'],
            'type' => ['required', 'string', Rule::in(RewardType::values())],
            'points_cost' => ['required', 'integer', 'min:1'],
            'value_amount' => ['required', 'numeric', 'min:0'],
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
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    public function messages(): array
    {
        return [
            'end_date.after_or_equal' => 'The end date must be after or equal to the start date.',
            'coupon_id.exists' => 'The selected coupon does not exist.',
        ];
    }
}
