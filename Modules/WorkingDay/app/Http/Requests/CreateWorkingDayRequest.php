<?php

namespace Modules\WorkingDay\Http\Requests;

use Illuminate\Validation\Rule;
use App\Enums\WorkingDayEnum;
use Illuminate\Foundation\Http\FormRequest;

class CreateWorkingDayRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'day_of_week' => [
                'required',
                'integer',
                'min:1',
                'max:7',
                Rule::in(WorkingDayEnum::values()),
            ],
            'open_time' => [
                'required',
                'date_format:H:i',
            ],
            'close_time' => [
                'required',
                'date_format:H:i',
                'after_or_equal:open_time',
            ],
            'store_id' => [
                'required',
                'exists:stores,id',
                Rule::unique('working_days', 'store_id')->where(function ($query) {
                    return $query->where('store_id', $this->input('store_id'))
                                 ->where('day_of_week', $this->input('day_of_week'));
                }),
            ],
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
        $this->merge(['store_id' => $this->store_id ?? auth()->user()->store_id]);
    }
}
