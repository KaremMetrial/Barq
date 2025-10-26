<?php

namespace Modules\Address\Http\Requests;

use App\Enums\AddressTypeEnum;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAddressRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'address_line_1' => ['nullable', 'string'],
            'address_line_2' => ['nullable', 'string'],
            'is_default' => ['nullable', 'boolean'],
            'type' => ['nullable', 'string', Rule::in(AddressTypeEnum::values())],
            'zone_id' => ['nullable', 'exists:zones,id'],
            'addressable_type' => ['required', 'string'],
            'addressable_id' => ['required', 'numeric'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'governorate_id' => ['nullable', 'exists:governorates,id'],
            'country_id' => ['nullable', 'exists:countries,id'],
            'apartment_number' =>  ['nullable', 'string'],
            'house_number' =>  ['nullable', 'string'],
            'street' =>  ['nullable', 'string'],
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
