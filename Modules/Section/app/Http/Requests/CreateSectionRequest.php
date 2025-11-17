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
        return [
            'name' => ['required', 'string', 'max:255'],
            'icon' => ['required', 'image', 'mimes:jpg,png,jpeg,gif,svg,webp', 'max:2048'],
            'is_active' => ['nullable', 'boolean'],
            'is_restaurant' => ['nullable', 'boolean'],
            'type' => ['nullable', 'string', Rule::in(SectionTypeEnum::values())],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['integer', Rule::exists('categories', 'id')],
            'countries' => ['required', 'array'],
            'countries.*' => ['integer', Rule::exists('countries', 'id')],
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
