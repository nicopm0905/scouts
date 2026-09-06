<?php

namespace App\Http\Requests\Finance;

use App\Enums\InvoiceCategory;
use App\Enums\InvoiceDirection;
use App\Enums\MemberRole;
use App\Models\Invoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Creación manual de una factura (p.ej. emitida rápida, sin fichero adjunto todavía). */
class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Invoice::class);
    }

    public function rules(): array
    {
        return [
            'direction' => ['required', Rule::in(InvoiceDirection::values())],
            'date' => ['required', 'date'],
            'supplier_or_client' => ['required', 'string', 'max:255'],
            'concept' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'vat' => ['nullable', 'numeric', 'min:0'],
            'category' => ['required', Rule::in(InvoiceCategory::values())],
            'branch' => ['nullable', Rule::in(MemberRole::values())],
        ];
    }
}
