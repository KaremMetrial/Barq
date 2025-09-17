<?php

namespace Modules\Unit\Http\Requests;

use App\Enums\UnitTypeEnum;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Http\FormRequest;

class CreateUnitRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            "name"=> ["required","string","max:255"],
            "abbreviation"=> ["required","string","max:255"],
            "type" => ["required","string",Rule::in(UnitTypeEnum::values())],
            "is_base" => ["nullable","boolean"],
            "conversion_to_base" => ["nullable","numeric"],
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
