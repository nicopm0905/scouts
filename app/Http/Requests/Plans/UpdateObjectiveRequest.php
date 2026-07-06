<?php

namespace App\Http\Requests\Plans;

use App\Enums\ObjectiveStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateObjectiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('objective')->branchPlan);
    }

    public function rules(): array
    {
        return [
            'description' => ['required', 'string'],
            'term' => ['nullable', 'integer', 'between:1,3'],
            'status' => ['required', Rule::in(ObjectiveStatus::values())],
            'position' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
