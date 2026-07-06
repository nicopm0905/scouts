<?php

namespace App\Http\Requests\Members;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHealthRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewSensitive', $this->route('member'));
    }

    public function rules(): array
    {
        return [
            'allergies' => ['nullable', 'string'],
            'intolerances' => ['nullable', 'string'],
            'medication' => ['nullable', 'string'],
            'observations' => ['nullable', 'string'],
            'health_card_number' => ['nullable', 'string', 'max:50'],
            'attachment' => ['nullable', 'file', 'max:10240'],
        ];
    }
}
