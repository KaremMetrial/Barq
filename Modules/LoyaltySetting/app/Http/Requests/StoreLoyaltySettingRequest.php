<?php

namespace Modules\LoyaltySetting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLoyaltySettingRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'country_id' => 'required|exists:countries,id|unique:loyalty_settings,country_id',
            'earn_rate' => 'required|numeric',
            'min_order_for_earn' => 'required|numeric',
            'referral_points' => 'required|numeric',
            'rating_points' => 'required|numeric',
            'currency_factor' => 'required|numeric'
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
