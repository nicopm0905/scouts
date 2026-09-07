<?php

namespace App\Http\Requests\Plans;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Programar en bloque las reuniones semanales de un tramo (trimestre) para la
 * rama de un plan.
 */
class ScheduleTermMeetingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('plans.manage');
    }

    public function rules(): array
    {
        return [
            'weekday' => ['required', 'integer', 'between:1,7'], // ISO: 1 = lunes … 7 = domingo
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'time' => ['required', 'date_format:H:i'],
            'duration_minutes' => ['required', 'integer', 'between:15,600'],
            'location' => ['nullable', 'string', 'max:255'],
            'skip_dates' => ['nullable', 'array'],
            'skip_dates.*' => ['date'],
        ];
    }

    public function messages(): array
    {
        return [
            'end_date.after_or_equal' => 'La fecha final debe ser posterior o igual a la de inicio.',
            'time.date_format' => 'La hora debe tener el formato HH:MM.',
        ];
    }
}
