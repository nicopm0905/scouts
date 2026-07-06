<?php

namespace App\Http\Requests\Plans;

use App\Enums\ObjectiveStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreObjectiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('branch_plan'));
    }

    public function rules(): array
    {
        return [
            'description' => ['required', 'string'],
            'term' => ['nullable', 'integer', 'between:1,3'],
            'status' => ['nullable', Rule::in(ObjectiveStatus::values())],
            'position' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
