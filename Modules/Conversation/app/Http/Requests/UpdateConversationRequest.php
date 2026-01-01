<?php

namespace Modules\Conversation\Http\Requests;

use Illuminate\Validation\Rule;
use App\Enums\ConversationTypeEnum;
use App\Enums\ConversationStatusEnum;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Http\FormRequest;

class UpdateConversationRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'type' => ['nullable', 'string', Rule::in(ConversationTypeEnum::values())],
            'start_time' => ['nullable','date'],
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
