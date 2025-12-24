<?php

namespace Modules\Withdrawal\Http\Requests;

use Modules\User\Models\User;
use App\Helpers\CurrencyHelper;
use Illuminate\Validation\Rule;
use Modules\Store\Models\Store;
use Modules\Couier\Models\Couier;
use App\Enums\WithdrawalStatusEnum;
use Illuminate\Foundation\Http\FormRequest;

class CreateWithdrawalRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (auth()->guard('vendor')->check()) {
            $vendor = auth()->guard('vendor')->user();
            $this->merge([
                'withdrawable_id' => $vendor->store_id,
                'withdrawable_type' => 'store',
            ]);

            $store = $vendor->store;
            if ($store) {
                $this->merge([
                    'currency_code' => $store->getCurrencyCode(),
                    'currency_factor' => $store->getCurrencyFactor(),
                    'amount' => CurrencyHelper::toMinorUnits($this->amount,$store->getCurrencyFactor())
                ]);
            }
        }

    }
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'withdrawable_id' => ['required', 'integer'],
            'withdrawable_type' => ['required', 'string','in:store,courier'],
            'amount' => [
                'required',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) {
                    $balance = $this->getBalance();

                    if ($balance === null || $value > $balance->available_balance) {
                        $fail('The withdrawal amount exceeds the available balance.');
                    }
                }
            ],
            'currency_code' => ['required', 'string', 'size:3'],
            'currency_factor' => ['required', 'numeric', 'min:0'],
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
        protected function getBalance()
    {
        // Determine the model (User, Courier, or Store) based on withdrawable_type and withdrawable_id
        $modelClass = $this->getWithdrawableModelClass();

        if ($modelClass) {
            $model = $modelClass::find($this->withdrawable_id);
            if ($model) {
                return $model->balance; // Assuming the 'balance' relation exists
            }
        }

        return null;
    }
    protected function getWithdrawableModelClass()
    {
        switch ($this->withdrawable_type) {
            case 'user':
                return User::class;
            case 'courier':
                return Couier::class;
            case 'store':
                return Store::class;
            default:
                return null;
        }
    }

}
