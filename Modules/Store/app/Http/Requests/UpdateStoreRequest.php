<?php

namespace Modules\Store\Http\Requests;

use App\Enums\StoreStatusEnum;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStoreRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', Rule::in(StoreStatusEnum::values())],
            'note' => ['nullable', 'string'],
            'logo' => ['nullable', 'image', 'mimes:jpg,png,jpeg,gif,svg', 'max:2048'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,png,jpeg,gif,svg', 'max:2048'],
            'phone' => ['nullable', 'string', 'unique:stores,phone,'. $this->route('store')],
            'message' => ['nullable', 'string'],
            'is_featured' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'is_closed' => ['nullable', 'boolean'],
            'avg_rate' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'section_id' => ['nullable', 'numeric', 'exists:sections,id'],
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

        $fields = ['name'];

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
