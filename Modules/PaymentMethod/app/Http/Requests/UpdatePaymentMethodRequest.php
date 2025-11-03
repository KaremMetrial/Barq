<?php

namespace Modules\PaymentMethod\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentMethodRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|max:100|unique:payment_methods,code,' . $this->route('payment_method'),
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
            'is_cod' => 'boolean',
            'config' => 'nullable|array',
            'sort_order' => 'integer|min:0',
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
