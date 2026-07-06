<?php

namespace App\Http\Requests\Events;

use Illuminate\Foundation\Http\FormRequest;

class UpdateChecklistItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'done' => ['sometimes', 'boolean'],
            'label' => ['sometimes', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
