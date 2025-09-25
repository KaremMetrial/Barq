<?php

namespace Modules\Couier\Http\Requests;

use App\Enums\UserStatusEnum;
use Illuminate\Validation\Rule;
use App\Enums\CouierAvaliableStatusEnum;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCouierRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            "first_name" => ["nullable", "string", "max:255"],
            "last_name" => ["nullable", "string", "max:255"],
            "email" => ["nullable", "email", "unique:couiers,email," . $this->route('couier')],
            "phone" => ["nullable", "string", "unique:couiers,phone," . $this->route('couier'), "max:255"],
            "password" => ["nullable", "string", "max:255"],
            "avatar" => ["nullable", "image", "mimes:jpg,png,jpeg,gif,svg", "max:2048"],
            "license_number" => ["nullable", "string", "unique:couiers,license_number," . $this->route('couier')],
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
}
