<?php

namespace App\Http\Requests\Members;

use App\Models\Member;
use Illuminate\Foundation\Http\FormRequest;

class ImportMembersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Member::class);
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'mimetypes:text/csv,text/plain,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'max:5120'],
        ];
    }
}
