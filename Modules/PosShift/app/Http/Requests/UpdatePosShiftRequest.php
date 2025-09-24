<?php

namespace Modules\PosShift\Http\Requests;

use App\Enums\SaleTypeEnum;
use App\Enums\PosShiftStatusEnum;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePosShiftRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'opened_at' => ['nullable', 'date_format:Y-m-d H:i:s'],
            'closed_at' => ['nullable', 'date_format:Y-m-d H:i:s', 'after:opened_at'],
            'opening_balance' => ['nullable', 'numeric'],
            'closing_balance' => ['nullable', 'numeric'],
            'total_sales' => ['nullable', 'numeric'],
            'pos_terminal_id' => ['nullable', 'integer', 'exists:pos_terminals,id'],
            'vendor_id' => ['nullable', 'integer', 'exists:vendors,id'],
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
