<?php

namespace Modules\Tag\Http\Requests;

use App\Enums\TagTypeEnum;
use App\Traits\FileUploadTrait;
use Illuminate\Validation\Rule;
use Modules\Tag\Models\Tag;
use Illuminate\Foundation\Http\FormRequest;

class CreateTagRequest extends FormRequest
{
    use FileUploadTrait;
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
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
