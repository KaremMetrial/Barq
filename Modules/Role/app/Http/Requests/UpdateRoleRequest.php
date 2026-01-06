<?php

namespace Modules\Role\Http\Requests;

use App\Enums\SaleTypeEnum;
use App\Enums\RoleStatusEnum;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRoleRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name'       => ['nullable', 'string', 'max:255'],
            'guard_name' => ['nullable', 'string', 'in:admin,vendor,user'],
            'permissions'=> ['nullable', 'array', 'min:1'],
            'permissions.*'=> ['nullable', 'string']
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
