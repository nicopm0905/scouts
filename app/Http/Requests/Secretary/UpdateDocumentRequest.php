<?php

namespace App\Http\Requests\Secretary;

use App\Enums\DocumentCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('document'));
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(DocumentCategory::values())],
            'file' => ['nullable', 'file', 'max:20480'],
            'external_url' => ['nullable', 'url', 'max:2048'],
            'expires_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
