<?php

namespace Modules\Slider\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Store\Models\Store;

class UpdateSliderRequest extends FormRequest
{
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
            "title" => ["nullable", "string", "max:255"],
            "body" => ["nullable", "string"],
            "image" => ["nullable", "image", "mimes:jpg,png,jpeg,gif,svg", "max:2048"],
            "button_text" => ["nullable", "string", "max:100"],
            "target" => ["nullable", "string", "max:255"],
            "target_id" => ["nullable", "integer", "min:1"],
            "is_active" => ["nullable", "boolean"],
            "sort_order" => ["nullable", "integer", "min:0"],
            "lang" => ["required", "string", Rule::in(Cache::get("languages.codes"))],
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

        $fields = ['title', 'body'];

        foreach ($fields as $field) {
            if (isset($validated[$field], $validated['lang'])) {
                $validated["{$field}:{$validated['lang']}"] = $validated[$field];
                unset($validated[$field]);
            }
        }
        unset($validated['lang']);
        $validated = array_filter($validated, fn($value) => !blank($value));

        $this->replace($validated);
    }
}