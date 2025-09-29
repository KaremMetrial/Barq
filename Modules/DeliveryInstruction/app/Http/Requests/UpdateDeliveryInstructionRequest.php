<?php

namespace Modules\DeliveryInstruction\Http\Requests;

use App\Enums\SaleTypeEnum;
use App\Enums\DeliveryInstructionStatusEnum;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDeliveryInstructionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string'],
            'description' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
            "lang" => ["required", "string", Rule::in(Cache::get("languages.codes"))],
        ];
    }
    protected function passedValidation(): void
    {
        $validated = $this->validated();

        $fields = ['title', 'description'];

        foreach ($fields as $field) {
            if (isset($validated[$field], $validated['lang'])) {
                $validated["{$field}:{$validated['lang']}"] = $validated[$field];
                unset($validated[$field]);
            }
        }
        unset($validated['lang']);

        $this->replace($validated);
    }
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
