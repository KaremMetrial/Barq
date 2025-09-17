<?php

namespace Modules\Category\Http\Requests;

use Modules\Category\Models\Category;
use Illuminate\Foundation\Http\FormRequest;

class CreateCategoryRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
          "name"=> ["required","string","max:255"],
          "icon" => ["nullable", "image", "mimes:jpg,png,jpeg,gif,svg", "max:2048"],
          "is_active" => ["nullable","boolean"],
          "sort_order" => ["nullable","numeric", "min:0", "unique:categories,sort_order"],
          "is_featured" => ["nullable","boolean"],
          "parent_id" => ["nullable","numeric", "exists:categories,id"],
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

        $validated = array_filter($validated, fn($value) => !blank($value));

        $this->replace($validated);
    }
    protected function prepareForValidation(): void
    {
        if ($this->filled('sort_order') === false) {
            $lastSortOrder = Category::max('sort_order') ?? 0;

            $this->merge([
                'sort_order' => $lastSortOrder + 1,
            ]);
        }
    }

}
