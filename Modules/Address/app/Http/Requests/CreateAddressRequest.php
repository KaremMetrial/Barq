<?php

namespace Modules\Address\Http\Requests;

use App\Enums\AddressTypeEnum;
use App\Traits\FileUploadTrait;
use Illuminate\Validation\Rule;
use Modules\Address\Models\Address;
use Illuminate\Foundation\Http\FormRequest;

class CreateAddressRequest extends FormRequest
{
    use FileUploadTrait;
    public function prepareForValidation()
    {
        $this->merge([
            'addressable_id' => auth('user')->check() ? auth('user')->user()->id : null,
        ]);
    }
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
            'address_line_1' => ['required', 'string'],
            'address_line_2' => ['nullable', 'string'],
            'is_default' => ['nullable', 'boolean'],
            'type' => ['nullable', 'string', Rule::in(AddressTypeEnum::values())],
            'zone_id' => ['nullable', 'exists:zones,id'], // Made optional since it will be auto-determined
            'addressable_type' => ['required', 'string'],
            'addressable_id' => ['required', 'numeric'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'governorate_id' => ['nullable', 'exists:governorates,id'],
            'country_id' => ['nullable', 'exists:countries,id'],
            'apartment_number' =>  ['nullable', 'string'],
            'house_number' =>  ['nullable', 'string'],
            'street' =>  ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:20'],
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
    protected function passedValidation(): void
    {
        $validated = $this->validated();

        $validated = array_filter($validated, fn($value) => !blank($value));

        $this->replace($validated);
    }

}
