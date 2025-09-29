<?php

namespace Modules\Ad\Http\Requests;

use App\Enums\AdTypeEnum;
use App\Enums\AdStatusEnum;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAdRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            "type" => ['required', Rule::in(AdTypeEnum::values())],
            'is_active' => ['nullable', 'boolean'],
            'status' => ['nullable', Rule::in(AdStatusEnum::values())],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date'],
            'media_path' => ['nullable', 'file', 'mimes:jpg,jpeg,png,gif,mp4,mov,avi', 'max:20480'],
            'adable_type' => ['nullable', 'string'],
            'adable_id' => ['nullable', 'integer'],
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

        $fields = ['title', 'description'];

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
