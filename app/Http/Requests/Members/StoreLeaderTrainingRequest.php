<?php

namespace App\Http\Requests\Members;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeaderTrainingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('member'));
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'obtained_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'attachment' => ['nullable', 'file', 'max:10240'],
        ];
    }
}
