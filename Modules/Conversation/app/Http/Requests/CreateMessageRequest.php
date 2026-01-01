<?php

namespace Modules\Conversation\Http\Requests;

use App\Enums\MessageTypeEnum;
use Illuminate\Validation\Rule;
use App\Enums\ConversationTypeEnum;
use Illuminate\Foundation\Http\FormRequest;

class CreateMessageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'content'         => 'required|string',
            'type'            => ['required', 'string', Rule::in(MessageTypeEnum::values())],
            'conversation_id' => 'required|exists:conversations,id',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
