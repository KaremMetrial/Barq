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
            'rating'                     => 'nullable|integer|between:1,5',
            'comment'                    => 'nullable|string|max:1000',
            'food_quality_rating'        => 'nullable|integer|between:1,5',
            'delivery_speed_rating'      => 'nullable|integer|between:1,5',
            'order_execution_speed_rating' => 'nullable|integer|between:1,5',
            'product_quality_rating'     => 'nullable|integer|between:1,5',
            'shopping_experience_rating' => 'nullable|integer|between:1,5',
            'overall_experience_rating'  => 'nullable|integer|between:1,5',
            'delivery_driver_rating'     => 'nullable|integer|between:1,5',
            'delivery_condition_rating'  => 'nullable|integer|between:1,5',
            'match_price_rating'         => 'nullable|integer|between:1,5',
            'image'                      => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
            'reviewable_id'              => 'nullable|integer',
            'reviewable_type'            => 'nullable|string|in:product,service',
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
