<?php

namespace Modules\ContactUs\Http\Requests;

use App\Enums\SectionTypeEnum;
use Illuminate\Validation\Rule;
use Modules\Section\Models\Section;
use Illuminate\Foundation\Http\FormRequest;
use App\Enums\ContactUsStatusEnum;

class CreateContactUsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'phone'        => ['required', 'string'],
            'content'      => ['required', 'string'],
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
