<?php

namespace App\Http\Requests\Admin;

use App\Enums\MemberRole;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', User::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(UserRole::values())],
            'branches' => ['array'],
            'branches.*' => [Rule::in(MemberRole::values())],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Ya existe una cuenta con ese correo.',
        ];
    }
}
