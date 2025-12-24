<?php

namespace Modules\Section\Http\Requests;

use App\Enums\SectionTypeEnum;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class CreateSectionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255', 'unique:section_translations,name'],
            'icon' => ['required', 'image', 'mimes:jpg,png,jpeg,gif,svg,webp', 'max:2048'],
            'is_active' => ['nullable', 'boolean'],
            'is_restaurant' => ['nullable', 'boolean'],
            'type' => ['required', 'string', Rule::in(SectionTypeEnum::values())],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['integer', Rule::exists('categories', 'id')],
            'countries' => ['required', 'array'],
            'countries.*' => ['integer', Rule::exists('countries', 'id')],
        ];

        // Make categories required if type is not delivery_company
        if ($this->type && $this->type !== SectionTypeEnum::DELIVERY_COMPANY->value) {
            $rules['categories'] = ['required', 'array'];
        }

        return $rules;
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
