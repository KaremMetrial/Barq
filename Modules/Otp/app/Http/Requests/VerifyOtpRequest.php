<?php

namespace Modules\Otp\Http\Requests;

use App\Enums\SaleTypeEnum;
use App\Enums\OtpStatusEnum;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
        public function prepareForValidation()
    {
        $phone = $this->input('phone');

        if (strpos($phone, '0') === 0) {
            $this->merge([
                'phone' => ltrim($phone, '0'),
            ]);
        }
    }
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'phone' => ['required','string', 'regex:/^\+?[1-9]\d{1,14}$/'],
            'phone_code' => ['required', 'string'],
            'otp' => ['required', 'string'],
            'model_type' => ['required', 'string'],
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
