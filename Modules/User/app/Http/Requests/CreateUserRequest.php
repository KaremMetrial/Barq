<?php

namespace Modules\User\Http\Requests;

use App\Enums\UserTypeEnum;
use App\Enums\UserStatusEnum;
use App\Enums\AddressTypeEnum;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class CreateUserRequest extends FormRequest
{
    public function prepareForValidation()
    {
        $phone = $this->input('phone');

        if (strpos($phone, '0') === 0) {
            $this->merge([
                'phone' => ltrim($phone, '0'),
            ]);
        }
    }
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            "first_name" => ["required", "string", "max:255"],
            "last_name" => ["required", "string", "max:255"],
            "email" => ["nullable", "string", "email", Rule::unique("users")],
            "phone_code" => ["required", "string", "max:255"],
            "phone" => ["required", "string", "regex:/^\+?[1-9]\d{1,14}$/","max:255", Rule::unique("users")->where(function ($query) {
                return $query->where('phone_code', $this->phone_code);
            })],
            "password" => ["nullable", "string"],
            "avatar" => ["nullable", "image", "mimes:jpeg,png,jpg,gif,svg", "max:2048"],
            "status" => ["nullable", "string", Rule::in(UserStatusEnum::values())],
            "provider" => ["nullable", "string", "max:255"],
            "provider_id" => ["nullable", "string", "max:255"],
            "balance" => ["nullable", "numeric"],
            "referral_code" => ["nullable", "string", "max:255"],
            "referral_id" => ["nullable", "integer", "exists:users,id"],
            "fcm_device" => ["nullable", "string", "max:255"],

            'address' => ['nullable', 'array'],
            'address.name' => ['nullable', 'string', 'max:255'],
            'address.latitude' => ['nullable', 'numeric'],
            'address.longitude' => ['nullable', 'numeric'],
            'address.address_line_1' => ['nullable', 'string'],
            'address.address_line_2' => ['nullable', 'string'],
            'address.is_default' => ['nullable', 'boolean'],
            'address.type' => ['nullable', 'string', Rule::in(AddressTypeEnum::values())],
            'address.zone_id' => ['nullable', 'exists:zones,id'],
            'address.city_id' => ['nullable', 'exists:cities,id'],
            'address.governorate_id' => ['nullable', 'exists:governorates,id'],
            'address.country_id' => ['nullable', 'exists:countries,id'],
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
