<?php

namespace Modules\Governorate\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Http\FormRequest;

class UpdateGovernorateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'country_id' => ['nullable', 'exists:countries,id'],
            'name' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'lang' => ['required', 'string', Rule::in(Cache::get("languages.codes"))],
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Handle the validation after it has passed.
     */
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
