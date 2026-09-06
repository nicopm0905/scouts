<?php

namespace App\Http\Requests\Finance;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Marcar varias filas de reparto como pagadas de una vez (uso típico: tras una
 * salida, la mayoría paga en efectivo en mano).
 */
class BulkMarkChargeMembersPaidRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('charge'));
    }

    public function rules(): array
    {
        return [
            'assignment_ids' => ['required', 'array', 'min:1'],
            'assignment_ids.*' => ['integer'],
            'payment_method' => ['required', Rule::in(PaymentMethod::values())],
        ];
    }
}
