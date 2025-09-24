<?php

namespace Modules\PosShift\Http\Requests;

use App\Enums\SaleTypeEnum;
use App\Enums\PosShiftTypeEnum;
use App\Enums\PosShiftStatusEnum;
use Illuminate\Validation\Rule;
use Modules\PosShift\Models\PosShift;
use Illuminate\Foundation\Http\FormRequest;

class CreatePosShiftRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'opened_at' => ['required', 'date_format:Y-m-d H:i:s'],
            'closed_at' => ['required', 'date_format:Y-m-d H:i:s', 'after:opened_at'],
            'opening_balance' => ['required', 'numeric'],
            'closing_balance' => ['nullable', 'numeric'],
            'total_sales' => ['nullable', 'numeric'],
            'pos_terminal_id' => ['required', 'integer', 'exists:pos_terminals,id'],
            'vendor_id' => ['required', 'integer', 'exists:vendors,id'],
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
