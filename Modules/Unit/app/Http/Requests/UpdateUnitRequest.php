<?php

namespace Modules\Unit\Http\Requests;

use App\Enums\UnitTypeEnum;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUnitRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            "name"=> ["nullable","string","max:255"],
            "abbreviation"=> ["nullable","string","max:255"],
            "type" => ["nullable","string",Rule::in(UnitTypeEnum::values())],
            "is_base" => ["nullable","boolean"],
            "conversion_to_base" => ["nullable","numeric"],
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

        $fields = ['name', 'abbreviation'];

        foreach ($fields as $field) {
            if (isset($validated[$field], $validated['lang'])) {
                $validated["{$field}:{$validated['lang']}"] = $validated[$field];
                unset($validated[$field]);
            }
        }
        unset($validated['lang']);

        $this->replace($validated);
    }
}
