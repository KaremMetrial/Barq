<?php

namespace Modules\Governorate\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGovernorateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'country_id' => ['required', 'exists:countries,id'],
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
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
