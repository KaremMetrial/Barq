<?php

namespace Modules\Offer\Http\Requests;

use App\Enums\SaleTypeEnum;
use App\Enums\OfferStatusEnum;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOfferRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'discount_type' => ['nullable', 'string', Rule::in(SaleTypeEnum::values())],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date', 'before_or_equal:end_date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_flash_sale' => ['nullable', 'boolean'],
            'has_stock_limit' => ['nullable', 'boolean'],
            'stock_limit' => ['nullable', 'numeric', 'min:0', 'required_if:has_stock_limit,true'],
            'is_active' => ['nullable', 'boolean'],
            'status' => ['nullable', 'string', Rule::in(OfferStatusEnum::values())],
            'offerable_type' => ['nullable', 'string'],
            'offerable_id' => ['nullable', 'integer'],
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
