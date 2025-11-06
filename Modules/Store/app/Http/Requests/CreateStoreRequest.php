<?php

namespace Modules\Store\Http\Requests;

use App\Enums\PlanTypeEnum;
use App\Enums\StoreStatusEnum;
use Illuminate\Validation\Rule;
use Modules\Store\Models\Store;
use App\Enums\DeliveryTypeUnitEnum;
use Illuminate\Validation\Rules\Password;
use Illuminate\Foundation\Http\FormRequest;

class CreateStoreRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'store' => ['required', 'array'],
            'store.name' => ['required', 'string', 'max:255'],
            'store.status' => ['nullable', 'string', Rule::in(StoreStatusEnum::values())],
            'store.note' => ['nullable', 'string'],
            'store.logo' => ['required', 'image', 'mimes:jpg,png,jpeg,gif,svg', 'max:2048'],
            'store.cover_image' => ['required', 'image', 'mimes:jpg,png,jpeg,gif,svg', 'max:2048'],
            'store.phone' => ['required', 'string', 'unique:stores,phone'],
            'store.message' => ['nullable', 'string'],
            'store.is_featured' => ['nullable', 'boolean'],
            'store.is_active' => ['nullable', 'boolean'],
            'store.is_closed' => ['nullable', 'boolean'],
            'store.avg_rate' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'store.section_id' => ['required', 'numeric', 'exists:sections,id'],
            'store.parent_id' => ['nullable', 'numeric', 'exists:stores,id'],
            'store.branch_type' => ['nullable', 'string', 'in:main,branch'],
            'store.orders_enabled' => ['nullable', 'boolean'],
            'store.delivery_service_enabled' => ['nullable', 'boolean'],
            'store.external_pickup_enabled' => ['nullable', 'boolean'],
            'store.product_classification' => ['nullable', 'boolean'],
            'store.self_delivery_enabled' => ['nullable', 'boolean'],
            'store.free_delivery_enabled' => ['nullable', 'boolean'],
            'store.minimum_order_amount' => ['nullable', 'numeric', 'min:0'],
            'store.delivery_time_min' => ['nullable', 'numeric', 'min:0'],
            'store.delivery_time_max' => ['nullable', 'numeric', 'min:0'],
            'store.delivery_type_unit' => ['nullable', 'string', Rule::in(DeliveryTypeUnitEnum::values())],
            'store.tax_rate' => ['nullable', 'numeric', 'min:0'],
            'store.order_interval_time' => ['nullable', 'numeric', 'min:0'],
            'store.service_fee_percentage' => ['nullable', 'numeric', 'min:0'],
            'store.commission_amount' => ['nullable', 'numeric', 'min:0'],
            'store.commission_type' => ['nullable', 'string', Rule::in(PlanTypeEnum::values())],


            'vendor' => ['required', 'array'],
            'vendor.first_name' => ['required', 'string', 'max:255'],
            'vendor.last_name' => ['required', 'string', 'max:255'],
            'vendor.email' => ['required', 'string', 'email', 'unique:vendors,email'],
            'vendor.phone' => ['required', 'string', 'unique:vendors,phone'],
            'vendor.password' => [
                'required',
                'string',
                Password::min(8)
                    ->mixedCase()
                    ->letters()
                    ->numbers()
                    ->symbols()
                    ->uncompromised()
            ],
            'vendor.is_owner' => ['required', 'boolean'],
            'vendor.is_active' => ['required', 'boolean'],
            'vendor.store_id' => ['nullable', 'integer', 'exists:stores,id'],
            'vendor.role_id' => ['nullable','string', 'exists:roles,id']
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
