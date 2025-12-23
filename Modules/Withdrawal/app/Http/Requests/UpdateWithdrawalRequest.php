<?php

namespace Modules\Withdrawal\Http\Requests;

use App\Enums\WithdrawalTypeEnum;
use App\Enums\WithdrawalStatusEnum;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Http\FormRequest;

class UpdateWithdrawalRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'withdrawable_id' => ['nullable', 'integer'],
            'withdrawable_type' => ['nullable', 'string','in:store,courier'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'currency_factor' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', Rule::in(WithdrawalStatusEnum::values())],
            'notes' => ['nullable', 'string'],
            'bank_name' => ['nullable', 'string'],
            'account_number' => ['nullable', 'string'],
            'iban' => ['nullable', 'string'],
            'swift_code' => ['nullable', 'string'],
            'account_holder_name' => ['nullable', 'string'],
            'processed_at' => ['nullable', 'date'],
            'processed_by' => ['nullable', 'integer'],
        ];
    }

    /**
     * Determine if the Withdrawal is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
