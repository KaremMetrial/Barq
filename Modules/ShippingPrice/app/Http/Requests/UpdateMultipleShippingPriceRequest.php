<?php

namespace Modules\ShippingPrice\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMultipleShippingPriceRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'zone_id' => ['required', 'integer'],
            'vehicles' => ['required', 'array'],
            'vehicles.*.vehicle_id' => ['required', 'integer'],
            'vehicles.*.base_price' => ['required', 'numeric'],
            'vehicles.*.max_price' => ['required', 'numeric'],
            'vehicles.*.per_km_price' => ['required', 'numeric'],
            'vehicles.*.max_cod_price' => ['required', 'numeric'],
            'vehicles.*.enable_cod' => ['required', 'boolean'],
            'vehicles.*.is_active' => ['required', 'boolean'],
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
