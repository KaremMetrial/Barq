<?php

namespace Modules\Vehicle\Http\Requests;

use App\Enums\VehicleStatusEnum;
use App\Enums\VehicleTypeEnum;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class CreateVehicleRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            "name" => ["required", "string", "max:255"],
            "description" => ["nullable", "string"],
            "is_active" => ["nullable", "boolean"],
            'icon' => ['required', 'image', 'mimes:jpg,jpeg,png,gif,mp4,mov,avi', 'max:2048'],
            'resize' => ['nullable', 'array', 'min:2', 'max:2'],
        ];
    }

    /**
     * Determine if the Vehicle is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
