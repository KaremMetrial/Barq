<?php

namespace Modules\Ad\Http\Requests;

use App\Enums\AdApplicableToEnum;
use App\Enums\AdStatusEnum;
use App\Enums\AdTypeEnum;
use App\Traits\FileUploadTrait;
use Illuminate\Validation\Rule;
use Modules\Ad\Models\Ad;
use Illuminate\Foundation\Http\FormRequest;

class CreateAdRequest extends FormRequest
{
    use FileUploadTrait;
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            "type" => ['required', Rule::in(AdTypeEnum::values())],
            'is_active' => ['nullable', 'boolean'],
            'status' => ['nullable', Rule::in(AdStatusEnum::values())],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date'],
            'media_path' => ['nullable', 'file', 'mimes:jpg,jpeg,png,gif,mp4,mov,avi', 'max:20480'],
            'adable_type' => ['nullable', 'string'],
            'adable_id' => ['nullable', 'integer'],
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
