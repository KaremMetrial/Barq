<?php

namespace Modules\User\Http\Requests;

use App\Enums\UserStatusEnum;
use App\Enums\UserTypeEnum;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class CreateUserRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            "first_name" => ["required", "string", "max:255"],
            "last_name"=> ["required", "string","max:255"],
            "email" => ["nullable", "string", "email", Rule::unique("users")],
            "phone" => ["required", "string", "max:255", Rule::unique("users")],
            "password" => ["nullable", "string"],
            "avatar" => ["nullable", "image", "mimes:jpeg,png,jpg,gif,svg", "max:2048"],
            "status" => ["nullable", "string", Rule::in(UserStatusEnum::values())],
            "provider" => ["nullable", "string", "max:255"],
            "provider_id" => ["nullable", "string", "max:255"],
            "balance" => ["nullable", "numeric"],
            "referral_code" => ["nullable", "string", "max:255"],
            "referral_id" => ["nullable", "integer", "exists:users,id"],
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
