<?php

namespace Modules\Vendor\Http\Requests;

use Illuminate\Validation\Rules\Password;
use Illuminate\Foundation\Http\FormRequest;

class CreateVendorRequest extends FormRequest
{

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'unique:vendors,email'],
            'phone' => ['required', 'string', 'unique:vendors,phone'],
            'password' => [
                'required',
                'string',
                Password::min(8)
                    ->mixedCase()
                    ->letters()
                    ->numbers()
                    ->symbols()
            ],
            'is_owner' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
            'store_id' => ['required', 'integer', 'exists:stores,id'],
            'role' => ['required','string', 'exists:roles,name']
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
