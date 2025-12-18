<?php

namespace Modules\Couier\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Carbon\Carbon;

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
        'store_id' => 'required|exists:stores,id',

        'days' => 'required|array|min:1',
        'days.*.day_of_week' => 'required|integer|between:0,6',
        'days.*.is_off_day' => 'required|boolean',
        'days.*.is_flexible' => 'required|boolean',

        'days.*.start_time' => 'nullable|date_format:H:i',
        'days.*.end_time' => 'nullable|date_format:H:i|after:days.*.start_time',

        'days.*.break_duration' => 'nullable|integer|min:0',
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
public function withValidator($validator)
{
    $validator->after(function ($validator) {

        foreach ($this->days as $index => $day) {

            $isFlexible = $day['is_flexible'] ?? false;
            $isOffDay   = $day['is_off_day'] ?? false;

            if ($isFlexible || $isOffDay) {
                continue;
            }

            if (empty($day['start_time']) || empty($day['end_time'])) {
                $validator->errors()->add(
                    "days.$index.start_time",
                    __('Start and end time are required for working non-flexible days')
                );
            }
        }
    });
}

}
