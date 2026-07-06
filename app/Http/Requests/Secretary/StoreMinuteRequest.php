<?php

namespace App\Http\Requests\Secretary;

use App\Enums\DocumentCategory;
use App\Models\Minute;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMinuteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Minute::class);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in([
                DocumentCategory::ActasConsejo->value,
                DocumentCategory::ActasAsamblea->value,
            ])],
            'held_on' => ['required', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'attendee_ids' => ['nullable', 'array'],
            'attendee_ids.*' => ['exists:members,id'],
            'items' => ['nullable', 'array'],
            'items.*.topic' => ['required', 'string', 'max:255'],
            'items.*.discussion' => ['nullable', 'string'],
            'items.*.agreement' => ['nullable', 'string'],
        ];
    }
}
