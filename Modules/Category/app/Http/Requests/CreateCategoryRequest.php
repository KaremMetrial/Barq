<?php

namespace Modules\Category\Http\Requests;

use App\Traits\FileUploadTrait;
use Modules\Category\Models\Category;
use Illuminate\Foundation\Http\FormRequest;

class CreateCategoryRequest extends FormRequest
{
    use FileUploadTrait;
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            "name" => ["required", "string", "max:255"],
            "icon" => ["nullable", "image", "mimes:jpg,png,jpeg,gif,svg", "max:2048"],
            "is_active" => ["nullable", "boolean"],
            "sort_order" => ["nullable", "numeric", "min:0", "unique:categories,sort_order"],
            "is_featured" => ["nullable", "boolean"],
            "parent_id" => ["nullable", "numeric", "exists:categories,id"],
            "store_id" => ["nullable", "numeric", "exists:stores,id"],
            'resize' => ['nullable', 'array', 'min:2', 'max:2'],
            'section_ids' => ['required', 'array'],
            'section_ids.*' => ['required', 'integer', 'exists:sections,id'],
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
        $mergeData = [];

        // Auto-assign sort_order if not provided
        if ($this->filled('sort_order') === false) {
            $lastSortOrder = Category::max('sort_order') ?? 0;
            $mergeData['sort_order'] = $lastSortOrder + 1;
        }

        // Auto-assign store_id for vendors
        if (auth('sanctum')->check() && auth('sanctum')->user()->tokenCan('vendor')) {
            $vendor = auth('sanctum')->user();
            if ($vendor && $vendor->store_id) {
                $mergeData['store_id'] = $vendor->store_id;
            }
        }

        if (!empty($mergeData)) {
            $this->merge($mergeData);
        }
    }
}
