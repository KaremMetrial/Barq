<?php

namespace Modules\City\Http\Requests;

use App\Enums\SectionTypeEnum;
use Illuminate\Validation\Rule;
use Modules\Section\Models\Section;
use Illuminate\Foundation\Http\FormRequest;

class CreateCityRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'governorate_id' => ['required', 'exists:governorates,id'],
            'name' => ['required', 'string', 'max:255'],
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
