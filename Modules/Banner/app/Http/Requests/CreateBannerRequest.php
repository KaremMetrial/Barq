<?php

namespace Modules\Banner\Http\Requests;

use App\Traits\FileUploadTrait;
use Illuminate\Validation\Rule;
use Modules\Banner\Models\Banner;
use Illuminate\Foundation\Http\FormRequest;

class CreateBannerRequest extends FormRequest
{
    use FileUploadTrait;
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            "title" => ["required", "string", "max:255"],
            "image" => ["required", "image", "mimes:jpg,png,jpeg,gif,svg", "max:2048"],
            "link" => ["nullable", "string", "max:255"],
            "start_date" => ["required", "date", "after_or_equal:today"],
            "end_date" => ["required", "date", "after:start_date"],
            "is_active" => ["nullable", "boolean"],
            "bannerable_type" => ["nullable", "string"],
            "bannerable_id" => ['nullable', 'integer'],
            'resize' => ['nullable', 'array', 'min:2', 'max:2'],
            "city_id" => ["nullable", "exists:cities,id"],
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
}
