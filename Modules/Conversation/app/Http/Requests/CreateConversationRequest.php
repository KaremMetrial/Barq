<?php

namespace Modules\Conversation\Http\Requests;

use App\Enums\ConversationTypeEnum;
use App\Enums\SectionTypeEnum;
use Illuminate\Validation\Rule;
use Modules\Section\Models\Section;
use Illuminate\Foundation\Http\FormRequest;

class CreateConversationRequest extends FormRequest
{
    public function prepareForValidation()
    {
        $this->merge([
            'user_id' => $this->input('user_id') ?? (auth('user')->check() ? auth('user')->id() : null),
            'admin_id' => $this->input('admin_id') ?? (auth('admin')->check() ? auth('admin')->id() : null),
            'couier_id' => $this->input('couier_id') ?? (auth('courier')->check() ? auth('courier')->id() : null),
        ]);
    }
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(ConversationTypeEnum::values())],
            'start_time' => ['nullable', 'date'],
            'end_time' => ['nullable', 'date'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'admin_id' => ['nullable', 'integer', 'exists:admins,id'],
            'couier_id' => ['nullable', 'integer', 'exists:couiers,id'],
            'order_id' => ['nullable', 'integer', 'exists:orders,id'],
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
