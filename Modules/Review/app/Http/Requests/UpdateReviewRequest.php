<?php

namespace Modules\Review\Http\Requests;

use App\Enums\SaleTypeEnum;
use App\Enums\ReviewStatusEnum;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Http\FormRequest;

class UpdateReviewRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'comment'                    => 'nullable|string|max:1000',
            'image'                      => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
            'reviewable_id'              => 'nullable|integer',
            'reviewable_type'            => 'nullable|string|in:product,service',
            'review_ratings'             => 'nullable|array',
            'review_ratings.*.rating_key_id' => 'required_with:review_ratings|exists:rating_keys,id',
            'review_ratings.*.rating'     => 'required_with:review_ratings|integer|between:1,5',
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
