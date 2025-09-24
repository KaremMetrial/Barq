<?php

namespace Modules\Option\Http\Requests;

use Illuminate\Validation\Rule;
use App\Enums\OptionInputTypeEnum;
use Illuminate\Foundation\Http\FormRequest;

class CreateOptionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
           "name"=> ["required", 'string', 'unique:option_translations,name'],
           "input_type"=> ["required", Rule::in(OptionInputTypeEnum::values())],
           "is_food_option"=> ["nullable", "boolean"],
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
