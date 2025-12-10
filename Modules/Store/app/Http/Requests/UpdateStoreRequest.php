<?php

namespace Modules\Store\Http\Requests;

use App\Enums\PlanTypeEnum;
use App\Enums\StoreStatusEnum;
use App\Enums\WorkingDayEnum;
use Illuminate\Validation\Rule;
use Modules\Store\Models\Store;
use App\Enums\DeliveryTypeUnitEnum;
use Illuminate\Validation\Rules\Password;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Cache;

class UpdateStoreRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'store' => ['nullable', 'array'],
            'store.name' => ['nullable', 'string', 'max:255'],
            'store.status' => ['nullable', 'string', Rule::in(StoreStatusEnum::values())],
            'store.note' => ['nullable', 'string'],
            'store.logo' => ['nullable', 'image', 'mimes:jpg,png,jpeg,gif,svg', 'max:2048'],
            'store.cover_image' => ['nullable', 'image', 'mimes:jpg,png,jpeg,gif,svg', 'max:2048'],
            'store.phone' => ['nullable', 'string', 'unique:stores,phone,' . $this->route('store')],
            'store.message' => ['nullable', 'string'],
            'store.is_featured' => ['nullable', 'boolean'],
            'store.is_active' => ['nullable', 'boolean'],
            'store.is_closed' => ['nullable', 'boolean'],
            'store.avg_rate' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'store.section_id' => ['nullable', 'numeric', 'exists:sections,id'],
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
            'store.active_status' => ['nullable', 'string', 'in:free,busy,close'],

            'address' => ['nullable', 'array'],
            'address.zone_id' => ['nullable', 'integer', 'exists:zones,id'],
            'address.latitude' => ['nullable', 'numeric'],
            'address.longitude' => ['nullable', 'numeric'],
            'address.address_line_1' => ['nullable', 'string'],

            'vendor' => ['nullable', 'array'],
            'vendor.first_name' => ['nullable', 'string', 'max:255'],
            'vendor.last_name' => ['nullable', 'string', 'max:255'],
            'vendor.email' => ['nullable', 'string', 'email', 'unique:vendors,email,' . $this->route('vendor')],
            'vendor.phone' => ['nullable', 'string', 'unique:vendors,phone,' . $this->route('vendor')],
            'vendor.password' => [
                'nullable',
                'string',
                Password::min(8)
                    ->mixedCase()
                    ->letters()
                    ->numbers()
                    ->symbols()
            ],
            'vendor.is_owner' => ['nullable', 'boolean'],
            'vendor.is_active' => ['nullable', 'boolean'],
            'vendor.store_id' => ['nullable', 'integer', 'exists:stores,id'],
            'vendor.role_id' => ['nullable', 'string', 'exists:roles,id'],

            'zones_to_cover' => ['nullable', 'array'],
            'zones_to_cover.*' => ['integer', 'exists:zones,id'],

            'working_days' => ['nullable', 'array'],
            'working_days.*.day_of_week' => ['nullable', 'integer', Rule::in(WorkingDayEnum::values())],
            'working_days.*.open_time' => ['nullable', 'date_format:H:i'],
            'working_days.*.close_time' => ['nullable', 'date_format:H:i', 'after:working_days.*.open_time'],

            "lang" => ["nullable", "string", Rule::in(Cache::get("languages.codes"))],
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
                $zone = \Modules\Zone\Models\Zone::findZoneByCoordinates($latitude, $longitude);
                if (!$zone || $zone->id != $zoneId) {
                    $validator->errors()->add('address.latitude', 'The provided latitude and longitude are not within the specified zone.');
                }
            }
        });
    }

    protected function passedValidation(): void
    {
        $validated = $this->validated();

        $fields = ['store.name'];

        foreach ($fields as $field) {
            if (isset($validated[$field], $validated['lang'])) {
                $validated["{$field}:{$validated['lang']}"] = $validated[$field];
                unset($validated[$field]);
            }
        }
        unset($validated['lang']);
        $this->replace($validated);
    }
}
