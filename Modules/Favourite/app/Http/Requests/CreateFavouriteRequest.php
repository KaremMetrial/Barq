<?php

namespace Modules\Favourite\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateFavouriteRequest extends FormRequest
{
    public function prepareForValidation()
    {
        $this->merge([
            'user_id' => auth('user')->user()->id,
        ]);
    }
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'favouriteable_id' => 'required|integer',
            'favouriteable_type' => 'required|string|in:store,product',
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
