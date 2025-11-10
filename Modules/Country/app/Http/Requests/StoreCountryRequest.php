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
