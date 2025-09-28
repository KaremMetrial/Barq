<?php

namespace Modules\AddOn\Http\Requests;

use App\Enums\AddOnApplicableToEnum;
use App\Enums\AddOnTypeEnum;
use App\Traits\FileUploadTrait;
use Illuminate\Validation\Rule;
use Modules\AddOn\Models\AddOn;
use Illuminate\Foundation\Http\FormRequest;

class CreateAddOnRequest extends FormRequest
{
    use FileUploadTrait;
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'applicable_to' => ['required', Rule::in(AddOnApplicableToEnum::values())],
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
    protected function passedValidation(): void
    {
        $validated = $this->validated();

        $validated = array_filter($validated, fn($value) => !blank($value));

        $this->replace($validated);
    }

}
