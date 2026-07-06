<?php

namespace App\Http\Requests\Finance;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MarkChargeMemberPaidRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('chargeMember')->charge);
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['required', Rule::in(PaymentMethod::values())],
            'notes' => ['nullable', 'string'],
        ];
    }
}
