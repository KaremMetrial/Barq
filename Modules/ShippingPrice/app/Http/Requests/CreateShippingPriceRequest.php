<?php

namespace Modules\ShippingPrice\Http\Requests;

use Modules\Zone\Models\Zone;
use Illuminate\Validation\Rule;
use App\Enums\ShippingPriceTypeEnum;
use Illuminate\Foundation\Http\FormRequest;

class CreateShippingPriceRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $zoneId = $this->input('zone_id');

        return [
            'zone_id' => ['required', 'integer', 'exists:zones,id'],
            'vehicles' => ['required', 'array', 'min:1'],
            'vehicles.*.vehicle_id' => [
                'required',
                'integer',
                'exists:vehicles,id',
                Rule::unique('shipping_prices', 'vehicle_id')
                    ->where('zone_id', $zoneId)
            ],
            'vehicles.*.base_price' => ['required', 'numeric'],
            'vehicles.*.max_price' => ['required', 'numeric'],
            'vehicles.*.per_km_price' => ['required', 'numeric'],
            'vehicles.*.max_cod_price' => ['required', 'numeric'],
            'vehicles.*.enable_cod' => ['required', 'boolean'],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'vehicles.*.vehicle_id.unique' => 'A shipping price already exists for this vehicle in the selected zone.',
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
