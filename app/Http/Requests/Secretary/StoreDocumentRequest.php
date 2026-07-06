<?php

namespace App\Http\Requests\Secretary;

use App\Enums\DocumentCategory;
use App\Models\Document;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Document::class);
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

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->hasFile('file') && empty($this->input('external_url'))) {
                $validator->errors()->add('file', 'Sube un fichero o indica una URL externa.');
            }
        });
    }
}
