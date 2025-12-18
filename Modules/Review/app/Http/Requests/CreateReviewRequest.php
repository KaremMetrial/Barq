<?php

namespace Modules\Review\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateReviewRequest  extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    Public function prepareForValidation()
    {
        $user = auth('user')->user();
        $this->merge([
            'reviewable_id' => $user->id,
            'reviewable_type' => 'user'
        ]);
    }
    public function rules(): array
    {
        return [
            'comment'                    => 'nullable|string|max:1000',
            'image'                      => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
            'order_id'                   => 'required|exists:orders,id',
            'reviewable_id'              => 'required|integer',
            'reviewable_type'            => 'required|string|in:product,service,user',
            'review_ratings'             => 'required|array',
            'review_ratings.*.rating_key_id' => 'required|exists:rating_keys,id',
            'review_ratings.*.rating'     => 'required|integer|between:1,5',
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
