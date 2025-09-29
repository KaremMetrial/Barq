<?php

namespace Modules\DeliveryInstruction\Http\Requests;

use App\Enums\SaleTypeEnum;
use App\Enums\DeliveryInstructionTypeEnum;
use App\Enums\DeliveryInstructionStatusEnum;
use Illuminate\Validation\Rule;
use Modules\DeliveryInstruction\Models\DeliveryInstruction;
use Illuminate\Foundation\Http\FormRequest;

class CreateDeliveryInstructionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string'],
            'description' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
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
