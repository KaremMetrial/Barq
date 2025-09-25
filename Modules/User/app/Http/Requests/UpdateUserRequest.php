<?php

namespace Modules\User\Http\Requests;

use App\Enums\UserTypeEnum;
use App\Enums\UserStatusEnum;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            "name" => ["nullable", "string", "max:255"],
            "email" => ["nullable", "string", "email", Rule::unique("users")->ignore($this->route('user'))],
            "phone" => ["nullable", "string", "max:255", Rule::unique("users")->ignore($this->route('user'))],
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
