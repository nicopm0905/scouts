<?php

namespace App\Http\Requests\Finance;

use App\Enums\ChargeType;
use App\Enums\MemberRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreChargeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Charge::class);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'amount' => ['required', 'numeric', 'min:0'],
            'due_date' => ['nullable', 'date'],
            'type' => ['required', Rule::in(ChargeType::values())],
            'event_id' => ['nullable', 'exists:events,id'],
            'sibling_discount_applies' => ['boolean'],
            'target_branches' => ['nullable', 'array'],
            'target_branches.*' => [Rule::in(MemberRole::values())],
            'member_ids' => ['nullable', 'array'],
            'member_ids.*' => ['exists:members,id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (empty($this->input('target_branches')) && empty($this->input('member_ids'))) {
                $validator->errors()->add('member_ids', 'Selecciona al menos una rama o un miembro destinatario.');
            }
        });
    }
}
