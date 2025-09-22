<?php

namespace Modules\PosTerminal\Http\Requests;

use App\Enums\SaleTypeEnum;
use App\Enums\PosTerminalStatusEnum;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePosTerminalRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'identifier' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('pos_terminals', 'identifier')->ignore($this->route('posterminal'))
            ],
            'name'      => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'store_id'  => ['nullable', 'integer', 'exists:stores,id'],
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
