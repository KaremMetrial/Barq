<?php

namespace Modules\Slider\Http\Requests;

use App\Traits\FileUploadTrait;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Store\Models\Store;

class CreateSliderRequest extends FormRequest
{
    use FileUploadTrait;
    
    protected function prepareForValidation(): void
    {
        if(isset($this->type) && $this->type == 'store'){
            $store = Store::find($this->target_id);
            if($store){
                $sectionType = $store->section->type;
                if($sectionType == 'restaurant'){
                    $this->merge([
                        'target' => 'restaurant',
                    ]);
                }else{
                    $this->merge([
                        'target' => 'regular_store',
                    ]);
                }
            }
        }
    }
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            "title" => ["required", "string", "max:255"],
            "body" => ["required", "string"],
            "image" => ["required", "image", "mimes:jpg,png,jpeg,gif,svg", "max:2048"],
            "button_text" => ["nullable", "string", "max:100"],
            "target" => ["nullable", "string", "max:255"],
            "target_id" => ["nullable", "integer", "min:1"],
            "is_active" => ["nullable", "boolean"],
            "sort_order" => ["nullable", "integer", "min:0"],
            'resize' => ['nullable', 'array', 'min:2', 'max:2'],
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