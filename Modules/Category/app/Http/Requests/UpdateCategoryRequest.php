<?php

namespace Modules\Category\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            "name" => ["nullable", "string", "max:255"],
            "icon" => ["nullable", "image", "mimes:jpg,png,jpeg,gif,svg", "max:2048"],
            "is_active" => ["nullable", "boolean"],
            "sort_order" => ["nullable", "numeric", "min:0", "unique:categories,sort_order"],
            "is_featured" => ["nullable", "boolean"],
            "parent_id" => ["nullable", "numeric", "exists:categories,id"],
            "lang" => ["required", "string", Rule::in(Cache::get("languages.codes"))],
            'store_id' => ['nullable', 'numeric', 'exists:stores,id'],
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
