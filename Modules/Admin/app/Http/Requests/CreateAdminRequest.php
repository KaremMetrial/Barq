<?php

namespace Modules\Admin\Http\Requests;

use App\Enums\AdminTypeEnum;
use App\Traits\FileUploadTrait;
use Illuminate\Validation\Rule;
use Modules\Admin\Models\Admin;
use Illuminate\Foundation\Http\FormRequest;

class CreateAdminRequest extends FormRequest
{
    use FileUploadTrait;
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            "first_name" => ["required", "string", "max:255"],
            "last_name" => ["required", "string", "max:255"],
            "email" => ["required", "email", "unique:admins,email"],
            "phone" => ["required", "string", "unique:admins,phone"],
            "password" => ["required", "string", "min:6"],
            "avatar" => ["nullable", "image", "mimes:jpg,png,jpeg,gif,svg", "max:2048"],
            "is_active" => ["nullable", "boolean"],
            "role" => ["required", "string", 'exists:roles,name'],
            'resize' => ['nullable', 'array', 'min:2', 'max:2'],
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
