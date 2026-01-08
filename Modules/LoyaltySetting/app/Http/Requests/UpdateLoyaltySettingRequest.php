<?php

namespace Modules\LoyaltySetting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLoyaltySettingRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'country_id' => 'nullable|exists:countries,id|unique:loyalty_settings,country_id,' . $this->route('loyaltysetting'),
            'earn_rate' => 'nullable|numeric',
            'min_order_for_earn' => 'nullable|numeric',
            'referral_points' => 'nullable|numeric',
            'rating_points' => 'nullable|numeric',
            'currency_factor' => 'nullable|numeric'
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
