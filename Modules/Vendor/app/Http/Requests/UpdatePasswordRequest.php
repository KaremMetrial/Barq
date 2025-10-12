<?php

namespace Modules\Vendor\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as PasswordRule;

class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'old_password' => ['required', 'string'],
            'password' => [
                'required',
                'string',
                'confirmed',
                PasswordRule::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
            ],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $vendor = $this->user('vendor');

            if (!Hash::check($this->old_password, $vendor->password)) {
                $validator->errors()->add('old_password', __('The old password is incorrect.'));
            }
        });
    }
}
