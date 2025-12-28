<?php

namespace Modules\Section\Http\Requests;

use App\Enums\SectionTypeEnum;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSectionRequest extends FormRequest
{
    public function prepareForValidation()
    {
        $this->merge([
            'categories' => $this->filterArray($this->input('categories', [])),
        ]);
    }
    private function filterArray(array $data): array
    {
        return array_filter($data, function ($value) {
            return !is_null($value) && $value !== '';
        });
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            "name" => ["nullable", "string", "max:255"],
            "icon" => ["nullable", "image", "mimes:jpg,png,jpeg,gif,svg", "max:2048"],
            'is_active' => ['nullable', 'boolean'],
            'is_restaurant' => ['nullable', 'boolean'],
            'type' => ['nullable', 'string', Rule::in(SectionTypeEnum::values())],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['integer', Rule::exists('categories', 'id')],
            'countries' => ['required', 'array'],
            'countries.*' => ['integer', Rule::exists('countries', 'id')],
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
}
