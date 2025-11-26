<?php

namespace Modules\PosTerminal\Http\Requests;

use App\Enums\SaleTypeEnum;
use App\Enums\PosTerminalTypeEnum;
use App\Enums\PosTerminalStatusEnum;
use Illuminate\Validation\Rule;
use Modules\PosTerminal\Models\PosTerminal;
use Illuminate\Foundation\Http\FormRequest;

class CreatePosTerminalRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function prepareForValidation()
    {
        if (auth('vendor')->check()) {
            $vendor = auth('vendor')->user();
            $this->merge([
                'store_id' => $vendor->store_id,
            ]);
        }
    }
    public function rules(): array
    {
        return [
            'identifier' => ['required', 'string', 'max:255', 'unique:pos_terminals,identifier'],
            'name'       => ['required', 'string', 'max:255'],
            'is_active'  => ['nullable', 'boolean'],
            'store_id'   => ['required', 'integer', 'exists:stores,id'],
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
