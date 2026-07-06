<?php

namespace App\Http\Requests\Members;

use App\Enums\LeaderQualification;
use App\Enums\MemberRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLeaderProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('member'));
    }

    public function rules(): array
    {
        return [
            'qualification' => ['required', Rule::in(LeaderQualification::values())],
            'sexual_offenses_certificate_date' => ['nullable', 'date'],
            'sexual_offenses_certificate_expires_at' => ['nullable', 'date'],
            'branches' => ['nullable', 'array'],
            'branches.*' => [Rule::in(array_map(fn ($b) => $b->value, MemberRole::branches()))],
        ];
    }
}
