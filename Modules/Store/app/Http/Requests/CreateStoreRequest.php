<?php

namespace Modules\Store\Http\Requests;

use App\Enums\PlanTypeEnum;
use App\Enums\StoreStatusEnum;
use App\Enums\WorkingDayEnum;
use Illuminate\Validation\Rule;
use Modules\Store\Models\Store;
use App\Enums\DeliveryTypeUnitEnum;
use App\Enums\SectionTypeEnum;
use Illuminate\Validation\Rules\Password;
use Illuminate\Foundation\Http\FormRequest;

class CreateStoreRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     */
    public function prepareForValidation()
    {
        $store = $this->filterArray($this->input('store', []));
        $address = $this->filterArray($this->input('address', []));
        $vendor = $this->filterArray($this->input('vendor', []));

        if (data_get($store, 'type') === 'delivery') {
            $deliverySection = \Modules\Section\Models\Section::where(
                'type',
                SectionTypeEnum::DELIVERY_COMPANY
            )->first();

            if ($deliverySection) {
                $store['section_id'] = $deliverySection->id;
            }
        } else {
            $store['type'] = 'store';
        }

        $this->merge([
            'store'   => $store,
            'address' => $address,
            'vendor'  => $vendor,
        ]);
    }

    private function filterArray(array $data): array
    {
        return array_filter($data, function ($value) {
            return !is_null($value) && $value !== '';
        });
    }

    public function rules(): array
    {
        return [
            'store' => ['required', 'array'],
            'store.name' => ['required', 'string', 'max:255'],
            'store.status' => ['nullable', 'string', Rule::in(StoreStatusEnum::values())],
            'store.note' => ['nullable', 'string'],
            'store.logo' => ['required', 'image', 'mimes:jpg,png,jpeg,gif,svg', 'max:2048'],
            'store.cover_image' => ['nullable', 'requiredIf:store.type,store', 'image', 'mimes:jpg,png,jpeg,gif,svg', 'max:2048'],
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
            'store.product_classification' => ['nullable', 'string'],
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
            'store.type' => ['nullable', 'string', 'in:delivery,store'],
            'store.currency_factor' => ['nullable', 'integer', 'min:1'],
            'store.iban' => ['nullable', 'string', 'max:255'],

            'address' => ['required', 'array'],
            'address.zone_id' => ['required', 'integer', 'exists:zones,id'],
            'address.latitude' => ['required', 'numeric'],
            'address.longitude' => ['required', 'numeric'],
            'address.address_line_1' => ['nullable', 'string'],


            'vendor' => ['nullable', 'required_if:store.type,store', 'array'],
            'vendor.first_name' => ['nullable', 'required_if:store.type,store', 'string', 'max:255'],
            'vendor.last_name' => ['nullable', 'required_if:store.type,store', 'string', 'max:255'],
            'vendor.email' => ['nullable', 'required_if:store.type,store', 'string', 'email', 'unique:vendors,email'],
            'vendor.phone' => ['nullable', 'required_if:store.type,store', 'string', 'unique:vendors,phone'],
            'vendor.password' => [
                'nullable',
                'required_if:store.type,store',
                'string',
                Password::min(8)
                    ->mixedCase()
                    ->letters()
                    ->numbers()
                    ->symbols()
            ],
            'vendor.is_owner' => ['nullable', 'required_if:store.type,store', 'boolean'],
            'vendor.is_active' => ['nullable', 'required_if:store.type,store', 'boolean'],
            'vendor.store_id' => ['nullable', 'integer', 'exists:stores,id'],
            'vendor.role_id' => ['nullable', 'string', 'exists:roles,id'],

            'zones_to_cover' => ['nullable', 'required_if:store.type,store', 'array'],
            'zones_to_cover.*' => ['integer', 'exists:zones,id'],

            'working_days' => ['nullable', 'required_if:store.type,store', 'array'],
            'working_days.*.day_of_week' => ['required', 'integer', Rule::in(WorkingDayEnum::values())],
            'working_days.*.open_time' => ['required', 'date_format:H:i'],
            'working_days.*.close_time' => ['required', 'date_format:H:i', 'after:working_days.*.open_time']
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $zoneId = $this->input('address.zone_id');
            $latitude = $this->input('address.latitude');
            $longitude = $this->input('address.longitude');

            if ($zoneId && $latitude && $longitude) {
                $zone = \Modules\Zone\Models\Zone::findZoneByCoordinates((float) $latitude, (float) $longitude);
                if (!$zone || $zone->id != $zoneId) {
                    $validator->errors()->add('address.latitude', 'The provided latitude and longitude are not within the specified zone.');
                }
            }

            // If store type is delivery, ensure the DELIVERY_COMPANY section exists and instruct to add it if missing
            $store = $this->input('store', []);
            if (data_get($store, 'type') == 'delivery') {
                $deliverySection = \Modules\Section\Models\Section::where('type', SectionTypeEnum::DELIVERY_COMPANY)->first();
                if (!$deliverySection) {
                    $validator->errors()->add('store.section_id', 'Please create a section of type "delivery_company" before creating a delivery store.');
                }
            }
        });
    }
}
