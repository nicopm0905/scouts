<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFinanceSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('settings.finance');
    }

    public function rules(): array
    {
        return [
            'group_name' => ['required', 'string', 'max:255'],
            'group_tax_id' => ['nullable', 'string', 'max:50'],
            'group_address' => ['nullable', 'string', 'max:255'],
            'group_postal_code' => ['nullable', 'string', 'max:20'],
            'group_city' => ['nullable', 'string', 'max:120'],
            'group_email' => ['nullable', 'email', 'max:255'],
            'group_phone' => ['nullable', 'string', 'max:30'],
            'sibling_discount_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'reminder_days_before' => ['required', 'integer', 'min:0', 'max:60'],
        ];
    }
}
