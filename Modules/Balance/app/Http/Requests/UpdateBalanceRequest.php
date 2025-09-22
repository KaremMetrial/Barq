<?php

namespace Modules\Balance\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBalanceRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'available_balance' => ['nullable', 'numeric'],
            'pending_balance'   => ['nullable', 'numeric'],
            'total_balance'     => ['nullable', 'numeric'],
            'balanceable_id'    => ['nullable', 'integer'],
            'balanceable_type'  => ['nullable', 'string', 'in:store'],
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
