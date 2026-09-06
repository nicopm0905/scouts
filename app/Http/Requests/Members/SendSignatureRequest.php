<?php

namespace App\Http\Requests\Members;

use Illuminate\Foundation\Http\FormRequest;

class SendSignatureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('documents.manage');
    }

    public function rules(): array
    {
        return [
            'member_ids' => ['required', 'array', 'min:1'],
            'member_ids.*' => ['integer', 'exists:members,id'],
        ];
    }
}
