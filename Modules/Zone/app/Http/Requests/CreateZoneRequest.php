<?php

namespace Modules\Zone\Http\Requests;

use App\Enums\SectionTypeEnum;
use Illuminate\Validation\Rule;
use Modules\Section\Models\Section;
use Illuminate\Foundation\Http\FormRequest;

class CreateZoneRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'city_id' => ['required', 'exists:cities,id'],
            'name' => ['required', 'string', 'max:255'],
            'area' => ['nullable', 'json'],
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
}
