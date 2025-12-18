<?php

namespace Modules\Review\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;

class UpdateRatingKeyRequest  extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    Public function prepareForValidation()
    {
    }
    public function rules(): array
    {
        return [
            'key' => 'nullable|string|max:255|unique:rating_keys,key',
            'is_active' => 'nullable|boolean',
            'label' => 'nullable|string|max:255',
            'lang' => ['nullable', 'string', Rule::in(Cache::get('languages.codes'))],
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

        $fields = ['label'];

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
