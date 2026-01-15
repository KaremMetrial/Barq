<?php

namespace Modules\Country\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCountryRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'code' => ["nullable", "string", "max:4", "unique:countries,code," . $this->route('country')],
            'is_active' => ["nullable", "boolean"],
            'currency_name' => ["nullable", "string", "max:255"],
            'flag' => ["nullable", "image", 'mimes:jpeg,png,jpg,gif,svg,webp', 'max:2048'],
            'currency_unit' => ['nullable', 'string', 'max:100'],
            'currency_factor' => ['nullable', 'integer', 'min:1'],
            'service_fee_percentage' => ['nullable', 'integer', 'min:0', 'max:100'],
            'tax_rate' => ['nullable', 'integer', 'min:0', 'max:100'],
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

        $this->replace($validated);
    }
}
