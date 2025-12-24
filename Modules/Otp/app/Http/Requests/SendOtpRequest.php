<?php

namespace Modules\Otp\Http\Requests;

use App\Enums\SaleTypeEnum;
use App\Enums\OtpTypeEnum;
use App\Enums\OtpStatusEnum;
use Illuminate\Validation\Rule;
use Modules\Otp\Models\Otp;
use Illuminate\Foundation\Http\FormRequest;

class SendOtpRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function prepareForValidation()
    {
        $phone = $this->input('phone');

        if (strpos($phone, '0') === 0) {
            $this->merge([
                'phone' => ltrim($phone, '0'),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            // 'phone' => ['required','string', 'regex:/^\+?\d{1,3}[-.\s]?\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4}$/'],
            'phone' => ['required','string', 'regex:/^\+?[1-9]\d{1,14}$/'],
            'phone_code' => [
                'nullable',
                'string',
                Rule::requiredIf(fn() => $this->input('model_type') != 'vendor')
            ],
            'model_type' => ['required','string'],
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
