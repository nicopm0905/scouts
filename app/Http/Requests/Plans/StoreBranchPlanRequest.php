<?php

namespace App\Http\Requests\Plans;

use App\Enums\MemberRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBranchPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\BranchPlan::class);
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
