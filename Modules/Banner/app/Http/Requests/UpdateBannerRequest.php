<?php

namespace Modules\Banner\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBannerRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            "title" => ["nullable", "string", "max:255"],
            "image" => ["nullable", "image", "mimes:jpg,png,jpeg,gif,svg", "max:2048"],
            "link" => ["nullable", "string", "max:255"],
            "start_date" => ["nullable", "date"],
            "end_date" => ["nullable", "date"],
            "is_active" => ["nullable", "boolean"],
            "bannerable_type" => ["nullable", "string"],
            "bannerable_id" => ['nullable', 'integer'],
            "city_id" => ["nullable", "exists:cities,id"],
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

        $fields = ['title'];

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
