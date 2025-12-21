<?php

namespace Modules\ShippingPrice\Http\Requests;

use App\Enums\ShippingPriceTypeEnum;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Http\FormRequest;

class UpdateShippingPriceRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'base_price' => ['nullable', 'numeric'],
            'max_price' => ['nullable', 'numeric'],
            'per_km_price' => ['nullable', 'numeric'],
            'max_cod_price' => ['nullable', 'numeric'],
            'enable_cod' => ['nullable', 'boolean'],
            'zone_id' => ['nullable', 'integer'],
            'vehicle_id' => ['nullable', 'integer'],
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
