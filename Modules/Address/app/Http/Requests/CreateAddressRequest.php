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
            'zone_id' => ['required', 'exists:zones,id'],
            'icon' => ['nullable', 'image', 'mimes:jpg,png,jpeg,gif,svg', 'max:2048'],
            'is_active' => ['nullable', 'boolean'],
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
