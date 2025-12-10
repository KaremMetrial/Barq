<?php

namespace Modules\Couier\Http\Requests;

use App\Enums\UserStatusEnum;
use App\Enums\CouierAvaliableStatusEnum;
use App\Enums\PlanTypeEnum;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class RegisterCourierRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            "first_name" => ["required", "string", "max:255"],
            "last_name" => ["required", "string", "max:255"],
            "email" => ["required", "string", "email", "unique:couriers,email"],
            "phone" => ["required", "string", "regex:/^\+?[1-9]\d{1,14}$/", "unique:couriers,phone"],
            "password" => ["required", "string", "min:6"],
            "avatar" => ["nullable", "image", "mimes:jpeg,png,jpg,gif,svg", "max:2048"],
            "license_number" => ["nullable", "string", "max:255"],
            "birthday" => ["nullable", "date"],
            "driving_license" => ["nullable", "string", "max:255"],
            "status" => ["nullable", "string", Rule::in(UserStatusEnum::values())],
            "available_status" => ["nullable", "string", Rule::in(CourierAvaliableStatusEnum::values())],
            "commission_type" => ["nullable", "string", Rule::in(PlanTypeEnum::values())],
            "commission_amount" => ["nullable", "numeric"],
            "store_id" => ["nullable", "exists:stores,id"],
            "fcm_device" => ["nullable", "string", "max:255"],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            "first_name.required" => __('validation.required', ['attribute' => 'first name']),
            "last_name.required" => __('validation.required', ['attribute' => 'last name']),
            "email.required" => __('validation.required', ['attribute' => 'email']),
            "email.email" => __('validation.email'),
            "email.unique" => __('validation.unique', ['attribute' => 'email']),
            "phone.required" => __('validation.required', ['attribute' => 'phone']),
            "phone.unique" => __('validation.unique', ['attribute' => 'phone']),
            "phone.regex" => __('validation.regex'),
            "password.required" => __('validation.required', ['attribute' => 'password']),
            "password.min" => __('validation.min.string', ['attribute' => 'password', 'min' => 6]),
            "avatar.image" => __('validation.image'),
            "avatar.mimes" => __('validation.mimes'),
            "avatar.max" => __('validation.max.file'),
            "commission_amount.numeric" => __('validation.numeric'),
            "store_id.exists" => __('validation.exists'),
        ];
    }
}
