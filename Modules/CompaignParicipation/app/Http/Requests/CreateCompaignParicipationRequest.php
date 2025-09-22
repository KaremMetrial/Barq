<?php

namespace Modules\CompaignParicipation\Http\Requests;

use App\Enums\SectionTypeEnum;
use Illuminate\Validation\Rule;
use Modules\Section\Models\Section;
use Illuminate\Foundation\Http\FormRequest;
use App\Enums\CompaignParicipationStatusEnum;

class CreateCompaignParicipationRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'compaign_id'  => ['required', 'integer', 'exists:compaigns,id'],
            'store_id'     => [
                'required',
                'integer',
                'exists:stores,id',
                Rule::unique('compaign_paricipations')->where(function ($query) {
                    return $query->where('compaign_id', $this->compaign_id);
                }),
            ],
            'status'       => ['nullable','string', Rule::in(CompaignParicipationStatusEnum::values())],
            'notes'        => ['nullable', 'string'],
            'responded_at' => ['nullable', 'date'],
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
