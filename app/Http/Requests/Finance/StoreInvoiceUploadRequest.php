<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

/** Subida masiva drag&drop: solo valida los ficheros; los datos se completan luego en la tabla editable. */
class StoreInvoiceUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Invoice::class);
    }

    public function rules(): array
    {
        return [
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['file', 'max:10240', 'mimes:pdf,jpg,jpeg,png'],
            'direction' => ['required', 'in:received,issued'],
        ];
    }
}
