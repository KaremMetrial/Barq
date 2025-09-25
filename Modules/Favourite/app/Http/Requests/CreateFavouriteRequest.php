<?php

namespace Modules\Favourite\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateFavouriteRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required','string','max:255'],
            'is_active' => ["nullable", "boolean"],
            'icon' => ['nullable','image', 'mimes:jpeg,png,jpg,gif,svg'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
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
