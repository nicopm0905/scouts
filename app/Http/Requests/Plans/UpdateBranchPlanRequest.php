<?php

namespace App\Http\Requests\Plans;

use App\Enums\MemberRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBranchPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('branch_plan'));
    }

    public function rules(): array
    {
        return [
            'branch' => ['required', Rule::in(MemberRole::values())],
            'school_year' => ['required', 'string', 'max:20', 'regex:/^\d{4}-\d{4}$/'],
            'description' => ['nullable', 'string'],
        ];
    }
}
