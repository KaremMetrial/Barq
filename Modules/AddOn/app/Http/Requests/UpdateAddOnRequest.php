<?php

namespace Modules\AddOn\Http\Requests;

use Illuminate\Validation\Rule;
use App\Enums\AddOnApplicableToEnum;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAddOnRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'applicable_to' => ['required', Rule::in(AddOnApplicableToEnum::values())],
            "lang" => ["required", "string", Rule::in(Cache::get("languages.codes"))],
            'store_id' => ['nullable', 'exists:stores,id'],
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

        $fields = ['name', 'description'];

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
