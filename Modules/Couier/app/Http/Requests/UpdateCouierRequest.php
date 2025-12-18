<?php

namespace Modules\Couier\Http\Requests;

use App\Enums\PlanTypeEnum;
use App\Enums\UserStatusEnum;
use Illuminate\Validation\Rule;
use App\Enums\CouierAvaliableStatusEnum;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCouierRequest extends FormRequest
{
    public function prepareForValidation()
    {
        $this->merge([
            'courier' => $this->filterArray($this->input('courier', [])),
            'address' => $this->filterArray($this->input('address', [])),
            'nationalID' => $this->filterArray($this->input('nationalID', [])),
            'zones_to_cover' => $this->filterArray($this->input('zones_to_cover', [])),
            'vehicle' => $this->filterArray($this->input('vehicle', [])),
            'attachment' => $this->filterArray($this->input('attachment', []))
        ]);
    }

    private function filterArray(array $data): array
    {
        return array_filter($data, function ($value) {
            return !is_null($value) && $value !== '';
        });
    }
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Courier
            "courier" => ["nullable", "array"],
            "courier.first_name" => ["nullable", "string", "max:255"],
            "courier.last_name" => ["nullable", "string", "max:255"],
            "courier.email" => ["nullable", "email", "unique:couiers,email,". $this->route('couier'), "max:255"],
            "courier.phone" => ["nullable", "string", "unique:couiers,phone,". $this->route('couier'), "max:255"],
            "courier.password" => ["nullable", "string", "max:255"],
            "courier.avatar" => ["nullable", "image", "mimes:jpg,png,jpeg,gif,svg", "max:2048"],
            "courier.license_number" => ["nullable", "string", "unique:couiers,license_number,". $this->route('couier')],
            "courier.available_status" => ["nullable", "string", Rule::in(CouierAvaliableStatusEnum::values())],
            "courier.avg_rate" => ["nullable", "numeric"],
            "courier.status" => ["nullable", "string", Rule::in(UserStatusEnum::values())],
            "courier.store_id" => ["nullable", "integer","exists:stores,id"],
            "courier.birthday" => ["nullable", "date", "date_format:Y-m-d"],
            "courier.commission_type" => ["nullable", "string", Rule::in(PlanTypeEnum::values())],
            "courier.commission_amount" => ["nullable", "numeric"],
            "courier.driving_license" => ["nullable", "image", "mimes:jpg,png,jpeg,gif,svg", "max:2048"],

            // Address
            'address' => ['nullable', 'array'],
            'address.zone_id' => ['nullable', 'integer', 'exists:zones,id'],
            'address.latitude' => ['nullable', 'numeric'],
            'address.longitude' => ['nullable', 'numeric'],
            'address.address_line_1' => ['nullable', 'string'],

            // National ID
            "nationalID" => ['nullable', "array"],
            "nationalID.national_id" => ['nullable', 'string', 'max:255'],
            "nationalID.front_image" => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,mp4,mov,avi', 'max:2048'],
            "nationalID.back_image" => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,mp4,mov,avi', 'max:2048'],

            // Zone To Cover
            'zones_to_cover' => ['nullable', 'array'],
            'zones_to_cover.*' => ['integer', 'exists:zones,id'],

            // Courier Vehicle
            "vehicle" => ["nullable", "array"],
            "vehicle.plate_number" => ['nullable', 'string', 'unique:couier_vehicles,plate_number,'. $this->route('couier'), 'max:255'],
            "vehicle.color" => ['nullable', 'string', 'max:255'],
            "vehicle.model" => ['nullable', 'string', 'max:255'],
            "vehicle.car_license" => ['nullable', 'image', 'mimes:jpg,png,jpeg,gif,svg', 'max:2048'],

            // Attachment
            "attachment" => ["nullable", "array"],
            "attachment.*.path" => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,mp4,mov,avi', 'max:2048'],
            "attachment.*.name" => ['nullable', 'string', 'max:255'],
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

}
