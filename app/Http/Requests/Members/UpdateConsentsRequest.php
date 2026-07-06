<?php

namespace App\Http\Requests\Members;

use App\Enums\ConsentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateConsentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewSensitive', $this->route('member'));
    }

    public function rules(): array
    {
        return [
            'consents' => ['required', 'array'],
            'consents.*.type' => ['required', Rule::in(ConsentType::values())],
            'consents.*.granted' => ['boolean'],
            'consents.*.signed_at' => ['nullable', 'date'],
        ];
    }
}
