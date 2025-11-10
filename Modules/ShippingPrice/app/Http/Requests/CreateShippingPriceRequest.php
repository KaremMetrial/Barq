<?php

namespace Modules\ShippingPrice\Http\Requests;

use App\Enums\ShippingPriceTypeEnum;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class CreateShippingPriceRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:shipping_price_translations,name'],
            'base_price' => ['required', 'numeric'],
            'max_price' => ['required', 'numeric'],
            'per_km_price' => ['required', 'numeric'],
            'max_cod_price' => ['required', 'numeric'],
            'enable_cod' => ['required', 'boolean'],
            'zone_id' => ['required', 'integer'],
            'vehicle_id' => ['required', 'integer'],
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
