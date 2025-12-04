<?php

namespace Modules\Reward\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RedeemRewardRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // No additional fields needed, reward_id comes from route
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth('sanctum')->check();
    }
}
