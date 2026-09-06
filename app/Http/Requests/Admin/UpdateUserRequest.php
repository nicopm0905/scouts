<?php

namespace App\Http\Requests\Admin;

use App\Enums\MemberRole;
use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('user'));
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'role' => ['required', Rule::in(UserRole::values())],
            'active' => ['boolean'],
            'branches' => ['array'],
            'branches.*' => [Rule::in(MemberRole::values())],
        ];
    }
}
