<?php

namespace Modules\Conversation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateMessageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'content'         => 'required|string',
            'type'            => 'required|string|in:text,image,video,file',
            'conversation_id' => 'required|exists:conversations,id',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
