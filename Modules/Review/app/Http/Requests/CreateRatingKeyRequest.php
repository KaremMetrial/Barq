<?php

namespace Modules\Review\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateRatingKeyRequest  extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    Public function prepareForValidation()
    {
    }
    public function rules(): array
    {
        return [
            'key' => 'required|string|max:255|unique:rating_keys,key',
            'is_active' => 'required|boolean',
            'label' => 'required|string|max:255',
            'section_id' => 'required|exists:sections,id',
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
