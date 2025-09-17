<?php

namespace Modules\Language\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLanguageRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            "name"=> ["nullable","string","max:255"],
            "code"=> ["nullable","string","max:255","unique:languages,code," . $this->route('language')],
            "native_name"=> ["nullable","string","max:255"],
            "direction"=> ["nullable","string","max:255","in:ltr,rtl"],
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
