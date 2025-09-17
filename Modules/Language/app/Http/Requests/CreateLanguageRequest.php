<?php

namespace Modules\Language\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateLanguageRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            "name"=> ["required","string","max:255"],
            "code"=> ["required","string","max:255"],
            "native_name"=> ["required","string","max:255"],
            "direction"=> ["required","string","max:255","enum:ltr,rtl"],
            "is_default"=> ["nullable","boolean"],
            "is_active"=> ["nullable","boolean"],
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
