<?php

namespace Modules\Balance\Http\Requests;

use App\Enums\BalanceTypeEnum;
use App\Traits\FileUploadTrait;
use Illuminate\Validation\Rule;
use Modules\Balance\Models\Balance;
use Illuminate\Foundation\Http\FormRequest;

class CreateBalanceRequest extends FormRequest
{
    use FileUploadTrait;
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'available_balance' => ['required', 'numeric'],
            'pending_balance'   => ['required', 'numeric'],
            'total_balance'     => ['required', 'numeric'],
            'balanceable_id'    => [
                'required',
                'integer',
                Rule::unique('balances')->where(function ($query) {
                    return $query->where('balanceable_type', $this->balanceable_type);
                }),
            ],
            'balanceable_type'  => ['required', 'string','in:store'],
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
