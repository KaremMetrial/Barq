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
    public function prepareForValidation()
    {
        $phone = $this->input('phone');

        if (strpos($phone, '0') === 0) {
            $this->merge([
                'phone' => ltrim($phone, '0'),
            ]);
        }
    }

    public function rules(): array
    {
        $userId = $this->route('user') ?? auth('user')->id();

        return [
            "first_name" => ["nullable", "string", "max:255"],
            "last_name" => ["nullable", "string", "max:255"],
            "email" => ["nullable", "string", "email", Rule::unique("users")->ignore($userId)],
            "phone" => [
                "nullable", 
                "string", 
                "regex:/^\+?[1-9]\d{1,14}$/",
                "max:255", 
                function ($attribute, $value, $fail) use ($userId) {
                    if ($value) {
                        // Check if phone is already used by another user
                        $existingUser = \Modules\User\Models\User::where('phone', $value)
                            ->where('phone_code', $this->phone_code)
                            ->where('id', '!=', $userId)
                            ->first();
                        
                        if ($existingUser) {
                            $fail('حقل phone تم استخدامه مسبقًا.');
                        }
                    }
                }
            ],
            "phone_code" => ["nullable", "string", "max:255"],
            "password" => ["nullable", "string", "min:8"],
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
