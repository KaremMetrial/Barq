<?php

namespace Modules\Tag\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTagRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            "name" => ["nullable", "string", "max:255"],
            "is_active" => ["nullable", "boolean"],
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
