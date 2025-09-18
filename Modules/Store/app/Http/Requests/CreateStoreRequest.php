<?php

namespace Modules\Store\Http\Requests;

use App\Enums\StoreStatusEnum;
use Illuminate\Validation\Rule;
use Modules\Store\Models\Store;
use Illuminate\Foundation\Http\FormRequest;

class CreateStoreRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'status' => ['nullable', 'string', Rule::in(StoreStatusEnum::values())],
            'note' => ['nullable', 'string'],
            'logo' => ['nullable', 'image', 'mimes:jpg,png,jpeg,gif,svg', 'max:2048'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,png,jpeg,gif,svg', 'max:2048'],
            'phone' => ['required', 'string', 'unique:stores,phone'],
            'message' => ['nullable', 'string'],
            'is_featured' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'is_closed' => ['nullable', 'boolean'],
            'avg_rate' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'section_id' => ['required', 'numeric', 'exists:sections,id'],
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
