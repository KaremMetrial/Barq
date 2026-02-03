<?php

namespace Modules\Country\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCountryRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required','string','max:255'],
            'code' => ["required", "string", "max:5", "unique:countries,code"],
            'currency_symbol' => ["required", "string", "max:5"],
            'is_active' => ["nullable", "boolean"],
            'currency_name' => ["required", "string", "max:255"],
            'flag' => ["required", "image", 'mimes:jpeg,png,jpg,gif,svg,webp', 'max:2048'],
            'currency_unit' => ['required', 'string', 'max:100'],
            'currency_factor' => ['required', 'integer', 'min:1'],
            'service_fee_percentage' => ['required', 'integer', 'min:0', 'max:100'],
            'tax_rate' => ['required', 'integer', 'min:0', 'max:100'],
            'timezone' => ['required', 'string', 'max:255'],
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

}
