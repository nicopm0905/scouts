<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class StoreCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('reserve', $this->route('item'));
    }

    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:1'],
            'event_id' => ['nullable', 'exists:events,id'],
            'member_id' => ['nullable', 'exists:members,id'],
            'checked_out_at' => ['required', 'date'],
            'expected_return_at' => ['nullable', 'date', 'after_or_equal:checked_out_at'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->event_id && ! $this->member_id) {
                $validator->errors()->add('event_id', 'Indica un evento o un responsable para la reserva.');
            }
        });
    }
}
