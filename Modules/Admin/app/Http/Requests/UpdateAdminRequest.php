<?php

namespace Modules\Admin\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAdminRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            "first_name" => ["nullable", "string", "max:255"],
            "last_name" => ["nullable", "string", "max:255"],
            "email" => ["nullable", "email", "unique:admins,email," . $this->route('admin')],
            "phone" => ["nullable", "string", "unique:admins,phone," . $this->route('admin')],
            "password" => ["nullable", "string", "min:6"],
            "avatar" => ["nullable", "image", "mimes:jpg,png,jpeg,gif,svg", "max:2048"],
            "is_active" => ["nullable", "boolean"],
            "role" => ["nullable", "string"],
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
