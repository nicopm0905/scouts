<?php

namespace App\Http\Requests\Plans;

use App\Enums\MscScope;
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
            'scope' => ['nullable', Rule::in(MscScope::values())],
            'line' => ['nullable', 'string', 'max:100'],
            'development_area' => ['nullable', 'string', 'max:50'],
            'content' => ['nullable', 'string'],
            'current_situation' => ['nullable', 'string'],
            'goal_verb' => ['nullable', 'string', 'max:50'],
            'goal_complement' => ['nullable', 'string'],
            'description' => ['required', 'string'],
            'term' => ['nullable', 'integer', 'between:1,3'],
            'evaluation' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(ObjectiveStatus::values())],
            'position' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * En el impreso MSC el objetivo se escribe en dos columnas (verbo +
     * complemento). Componemos con ellas el texto del objetivo para que quien
     * rellene el formulario no tenga que repetirlo.
     */
    protected function prepareForValidation(): void
    {
        if (blank($this->input('description'))) {
            $composed = trim($this->input('goal_verb', '').' '.$this->input('goal_complement', ''));

            if ($composed !== '') {
                $this->merge(['description' => $composed]);
            }
        }
    }
}
