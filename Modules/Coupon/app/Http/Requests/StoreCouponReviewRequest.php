<?php

namespace Modules\Coupon\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCouponReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000|min:10'
        ];
    }

    public function messages(): array
    {
        return [
            'rating.required' => 'Please provide a rating for this coupon.',
            'rating.min' => 'Rating must be at least 1 star.',
            'rating.max' => 'Rating cannot exceed 5 stars.',
            'comment.min' => 'Comment must be at least 10 characters long.',
            'comment.max' => 'Comment cannot exceed 1000 characters.'
        ];
    }
}