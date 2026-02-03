<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendNotificationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled in controller
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'translations' => 'required|array',
            'translations.*.title' => 'required_with:translations|string|max:255',
            'translations.*.body' => 'required_with:translations|string|max:500',
            'data' => 'nullable|array',
            'target_type' => [
                'required',
                Rule::in(['all_users', 'specific_users', 'top_users'])
            ],
            'target_data' => 'nullable|array',
            'target_data.user_ids' => 'array',
            'target_data.user_ids.*' => 'integer|exists:users,id',
            'top_users_count' => 'nullable|integer|min:1|max:1000',
            'performance_metric' => [
                'nullable',
                Rule::in(['order_count', 'total_spent', 'loyalty_points', 'avg_rating'])
            ],
            'is_scheduled' => 'boolean',
            'scheduled_at' => 'nullable|date|after:now',
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Notification title is required.',
            'title.max' => 'Notification title cannot exceed 255 characters.',
            'body.required' => 'Notification body is required.',
            'body.max' => 'Notification body cannot exceed 500 characters.',
            'target_data.user_ids.*.exists' => 'One or more selected users do not exist.',
            'top_users_count.min' => 'Top users count must be at least 1.',
            'top_users_count.max' => 'Top users count cannot exceed 1000.',
            'scheduled_at.after' => 'Scheduled time must be in the future.',
        ];
    }
}
