<?php

namespace App\Http\Requests\Photos;

use App\Models\Album;
use Illuminate\Foundation\Http\FormRequest;

class StoreAlbumRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Album::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'event_id' => ['nullable', 'exists:events,id'],
            'visibility' => ['required', 'in:internal,publishable'],
        ];
    }
}
