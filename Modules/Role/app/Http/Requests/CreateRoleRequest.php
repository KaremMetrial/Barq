<?php

namespace Modules\Role\Http\Requests;

use App\Enums\SaleTypeEnum;
use App\Enums\RoleTypeEnum;
use App\Enums\RoleStatusEnum;
use Illuminate\Validation\Rule;
use Modules\Role\Models\Role;
use Illuminate\Foundation\Http\FormRequest;

class CreateRoleRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'max:255'],
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
