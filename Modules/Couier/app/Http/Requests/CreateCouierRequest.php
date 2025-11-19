<?php

namespace Modules\Couier\Http\Requests;

use App\Enums\UserStatusEnum;
use App\Traits\FileUploadTrait;
use Illuminate\Validation\Rule;
use Modules\Couier\Models\Couier;
use App\Enums\CouierAvaliableStatusEnum;
use Illuminate\Foundation\Http\FormRequest;

class CreateCouierRequest extends FormRequest
{
    use FileUploadTrait;
    /**
     * Get the validation rules that apply to the request.
     */
    public function prepareForValidation()
    {
        $this->merge([
            'courier' => $this->filterArray($this->input('courier', [])),
            'address' => $this->filterArray($this->input('address', [])),
            'nationalID'
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
            // Courier
            "courier" => ["required", "array"],
            "courier.first_name" => ["required", "string", "max:255"],
            "courier.last_name" => ["required", "string", "max:255"],
            "courier.email" => ["required", "email", "unique:couiers,email", "max:255"],
            "courier.phone" => ["required", "string", "unique:couiers,phone", "max:255"],
            "courier.password" => ["required", "string", "max:255"],
            "courier.avatar" => ["nullable", "image", "mimes:jpg,png,jpeg,gif,svg", "max:2048"],
            "courier.license_number" => ["required", "string", "unique:couiers,license_number"],
            "courier.avaliable_status" => ["nullable", "string", Rule::in(CouierAvaliableStatusEnum::values())],
            "courier.avg_rate" => ["nullable", "double"],
            "courier.status" => ["nullable", "string", Rule::in(UserStatusEnum::values())],
            "courier.store_id" => ["required", "integer","exist:stores,id"],
            "courier.birthday" => ["required", "date", "date_format:Y-m-d"],

            // Address
            'address' => ['required', 'array'],
            'address.zone_id' => ['required', 'integer', 'exists:zones,id'],
            'address.latitude' => ['required', 'numeric'],
            'address.longitude' => ['required', 'numeric'],
            'address.address_line_1' => ['nullable', 'string'],

            // National ID
            "nationalID" => ['required', "array"],
            "nationalID.national_id" => ['required', 'string', 'max:255'],
            "nationalID.front_image" => ['required', 'image', 'mimes:jpg,jpeg,png,gif,mp4,mov,avi', 'max:2048'],
            "nationalID.back_image" => ['required', 'image', 'mimes:jpg,jpeg,png,gif,mp4,mov,avi', 'max:2048'],

            // Zone To Cover
            'zones_to_cover' => ['required', 'array'],
            'zones_to_cover.*' => ['integer', 'exists:zones,id'],

            // Courier Vehicle
            "vehicle" => ["required", "array"],
            "vehicle.plate_number" => ['required', 'string', 'unique:couier_vehicles,plate_number', 'max:255'],
            "vehicle.color" => ['required', 'string', 'max:255'],
            "vehicle.model" => ['required', 'string', 'max:255'],

            // Attachment
            "attachment" => ["required", "array"],
            "attachment.*.path" => ['required', 'image', 'mimes:jpg,jpeg,png,gif,mp4,mov,avi', 'max:2048'],
            "attachment.*.name" => ['required', 'string', 'max:255'],
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
