<?php

namespace Modules\Conversation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMessageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'content' => 'sometimes|string',
            'type'    => 'sometimes|string|in:text,image,video,file',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
