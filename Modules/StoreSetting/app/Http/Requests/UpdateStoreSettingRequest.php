<?php

namespace Modules\StoreSetting\Http\Requests;

use Illuminate\Validation\Rule;
use App\Enums\DeliveryTypeUnitEnum;
use App\Enums\StoreSettingStatusEnum;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStoreSettingRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'orders_enabled' => ['nullable', 'boolean'],
            'delivery_service_enabled' => ['nullable', 'boolean'],
            'external_pickup_enabled' => ['nullable', 'boolean'],
            'product_classification' => ['nullable', 'string', 'max:255'],
            'self_delivery_enabled' => ['nullable', 'boolean'],
            'free_delivery_enabled' => ['nullable', 'boolean'],
            'minimum_order_amount' => ['nullable', 'numeric', 'min:0'],
            'delivery_time_min' => ['nullable', 'integer', 'min:0'],
            'delivery_time_max' => ['nullable', 'integer', 'min:0'],
            'delivery_type_unit' => ['nullable', 'string', Rule::in(DeliveryTypeUnitEnum::values())],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'service_fee_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'order_interval_time' => ['nullable', 'integer', 'min:1'],
            'store_id' => ['nullable' ,'integer', 'exists:stores,id',Rule::unique('store_settings', 'store_id')->ignore($this->store_id)],
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
    protected function prepareForValidation(): void
    {
        $this->merge(['store_id' => $this->store_id ?? auth()->user()->store_id]);
    }
}
