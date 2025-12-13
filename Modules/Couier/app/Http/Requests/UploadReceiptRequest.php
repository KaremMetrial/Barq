<?php

namespace Modules\Couier\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadReceiptRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled in controller via middleware
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'image',
                'mimes:jpeg,jpg,png,gif',
                'max:5120', // 5MB max
            ],
            'type' => [
                'required',
                'in:pickup_product,pickup_receipt,delivery_proof,customer_signature',
            ],
            'metadata' => [
                'nullable',
                'array',
            ],
            'metadata.latitude' => [
                'nullable',
                'numeric',
                'between:-90,90',
            ],
            'metadata.longitude' => [
                'nullable',
                'numeric',
                'between:-180,180',
            ],
            'metadata.timestamp' => [
                'nullable',
                'date',
            ],
            'metadata.device_info' => [
                'nullable',
                'array',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'file.required' => __('Receipt file is required'),
            'file.image' => __('File must be an image'),
            'file.mimes' => __('File type must be jpeg, jpg, png, or gif'),
            'file.max' => __('File size must not exceed 5MB'),
            'type.required' => __('Receipt type is required'),
            'type.in' => __('Invalid receipt type'),
            'metadata.latitude.between' => __('Invalid latitude coordinates'),
            'metadata.longitude.between' => __('Invalid longitude coordinates'),
            'metadata.timestamp.date' => __('Invalid timestamp format'),
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'file' => __('Receipt Image'),
            'type' => __('Receipt Type'),
            'metadata.latitude' => __('Latitude'),
            'metadata.longitude' => __('Longitude'),
            'metadata.timestamp' => __('Timestamp'),
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Add current timestamp if not provided
        if (!$this->has('metadata.timestamp')) {
            $this->merge([
                'metadata' => array_merge($this->get('metadata', []), [
                    'timestamp' => now()->toISOString(),
                ])
            ]);
        }
    }
}
