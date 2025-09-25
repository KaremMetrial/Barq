<?php

namespace Modules\Couier\Http\Requests;

use App\Enums\UserStatusEnum;
use App\Traits\FileUploadTrait;
use Illuminate\Validation\Rule;
use Modules\Couier\Models\Couier;
use App\Enums\CouierAvaliableStatusEnum;
use Illuminate\Foundation\Http\FormRequest;

class CreateCouierRequest extends FormRequest
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
            "email" => ["required", "email", "unique:couiers,email"],
            "phone" => ["required", "string", "unique:couiers,phone", "max:255"],
            "password" => ["required", "string", "max:255"],
            "avatar" => ["nullable", "image", "mimes:jpg,png,jpeg,gif,svg", "max:2048"],
            "license_number" => ["required", "string", "unique:couiers,license_number"],
            "avaliable_status" => ["nullable", "string", Rule::in(CouierAvaliableStatusEnum::values())],
            "avg_rate" => ["nullable", "double"],
            "status" => ["nullable", "string", Rule::in(UserStatusEnum::values())],
            "store_id" => ["nullable", "integer"],
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
