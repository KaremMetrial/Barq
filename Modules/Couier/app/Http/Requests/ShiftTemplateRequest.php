<?php

namespace Modules\Couier\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShiftTemplateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'is_active' => 'boolean',
            'is_flexible' => 'boolean',
            'store_id' => 'nullable|exists:stores,id',
            'days' => 'required|array|min:1',
            'days.*.day_of_week' => 'required|integer|between:0,6',
            'days.*.start_time' => 'required_if:days.*.is_off_day,false|date_format:H:i:s',
            'days.*.end_time' => 'required_if:days.*.is_off_day,false|date_format:H:i:s|after:days.*.start_time',
            'days.*.break_duration' => 'nullable|integer|min:0',
            'days.*.is_off_day' => 'boolean'
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if (auth('vendor')->check()) {
            $this->merge([
                'store_id' => auth('vendor')->user()->store_id,
            ]);
        }
    }
}
