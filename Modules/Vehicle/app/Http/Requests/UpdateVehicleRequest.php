<?php

namespace Modules\Vehicle\Http\Requests;

use App\Enums\VehicleTypeEnum;
use App\Enums\VehicleStatusEnum;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Http\FormRequest;

class UpdateVehicleRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            "name" => ["nullable", "string", "max:255"],
            "description" => ["nullable", "string"],
            "is_active" => ["nullable", "boolean"],
            "lang" => ["required", "string", Rule::in(Cache::get("languages.codes"))],
        ];
    }

    /**
     * Determine if the Vehicle is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
      protected function passedValidation(): void
    {
        $validated = $this->validated();

        $fields = ['name','description'];

        foreach ($fields as $field) {
            if (isset($validated[$field], $validated['lang'])) {
                $validated["{$field}:{$validated['lang']}"] = $validated[$field];
                unset($validated[$field]);
            }
        }
        unset($validated['lang']);
        $this->replace($validated);
    }
}
