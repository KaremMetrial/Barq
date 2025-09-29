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
    public function rules(): array
    {
        return [
            'phone' => ['required','string'],
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
