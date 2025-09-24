<?php

namespace Modules\Option\Http\Requests;

use App\Enums\SaleTypeEnum;
use App\Enums\OptionStatusEnum;
use Illuminate\Validation\Rule;
use App\Enums\OptionInputTypeEnum;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOptionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            "name" => ["nullable", 'string', 'unique:option_translations,name,'.$this->route('option')],
            "input_type" => ["nullable", Rule::in(OptionInputTypeEnum::values())],
            "is_food_option" => ["nullable", "boolean"],
            "lang" => ["required", "string", Rule::in(Cache::get("languages.codes"))],
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
    protected function passedValidation(): void
    {
        $validated = $this->validated();

        $fields = ['name'];

        foreach ($fields as $field) {
            if (isset($validated[$field], $validated['lang'])) {
                $validated["{$field}:{$validated['lang']}"] = $validated[$field];
                unset($validated[$field]);
            }
        }
        unset($validated['lang']);
        $validated = array_filter($validated, fn($value) => !blank($value));

        $this->replace($validated);
    }
}
