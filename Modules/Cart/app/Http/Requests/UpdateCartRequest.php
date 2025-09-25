<?php

namespace Modules\Cart\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCartRequest extends FormRequest
{
        public function prepareForValidation(): void
    {
        // Filter input data before validation to clean up null or empty values
        $this->merge([
            'cart' => $this->filterArray($this->input('cart', [])),
        ]);
    }

    private function filterArray(array $data): array
    {
        // Filter out null or empty values from the array
        return array_filter($data, fn($value) => !is_null($value) && $value !== '');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            "cart" => ["nullable", "array"],
            "cart.pos_shift_id" => ["nullable", "integer", "exists:pos_shifts,id"],
            "cart.store_id" => ["nullable", "integer", "exists:stores,id"],
            "cart.user_id" => ["nullable", "integer", "exists:users,id"],
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
