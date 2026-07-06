<?php

namespace App\Http\Requests\Members;

use App\Enums\FamilyRelationship;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttachFamilyMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('member'));
    }

    public function rules(): array
    {
        return [
            'family_id' => ['required', 'exists:families,id'],
            'relationship' => ['required', Rule::in(FamilyRelationship::values())],
        ];
    }
}
