<?php

namespace Modules\ContactUs\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Http\FormRequest;
use App\Enums\ContactUsStatusEnum;

class UpdateContactUsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'compaign_id'  => ['nullable', 'integer', 'exists:compaigns,id'],
            'store_id'     => ['nullable', 'integer', 'exists:stores,id'],
            'status'       => ['nullable', Rule::in(ContactUsStatusEnum::values())],
            'notes'        => ['nullable', 'string'],
            'responded_at' => ['nullable', 'date'],
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
