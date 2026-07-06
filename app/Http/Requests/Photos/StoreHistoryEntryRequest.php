<?php

namespace App\Http\Requests\Photos;

use App\Models\HistoryEntry;
use Illuminate\Foundation\Http\FormRequest;

class StoreHistoryEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', HistoryEntry::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'year' => ['required', 'integer', 'min:1907', 'max:'.(now()->year + 1)],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'position' => ['nullable', 'integer', 'min:0'],
            'published' => ['boolean'],
            'photo' => ['nullable', 'image', 'max:10240'],
        ];
    }
}
