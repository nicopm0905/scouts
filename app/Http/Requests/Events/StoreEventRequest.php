<?php

namespace App\Http\Requests\Events;

use App\Enums\EventType;
use App\Enums\MemberRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // La autorización real la hace EventPolicy en el controlador.
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(EventType::values())],
            'start_at' => ['required', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'location' => ['nullable', 'string', 'max:255'],
            'location_city' => ['nullable', 'string', 'max:255'],
            'coordinator_id' => ['nullable', 'exists:users,id'],
            'has_eucharist' => ['boolean'],
            'has_hike' => ['boolean'],
            'theme_description' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'branches' => ['nullable', 'array'],
            'branches.*' => [Rule::in(array_map(fn ($b) => $b->value, MemberRole::branches()))],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'El título es obligatorio.',
            'type.required' => 'Selecciona un tipo de evento.',
            'start_at.required' => 'La fecha de inicio es obligatoria.',
            'end_at.after_or_equal' => 'La fecha de fin debe ser posterior o igual al inicio.',
        ];
    }
}
