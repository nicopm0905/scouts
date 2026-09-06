<?php

namespace App\Http\Requests\Members;

use App\Enums\MemberRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('attendance.manage');
    }

    public function rules(): array
    {
        return [
            'branch' => ['required', Rule::in(MemberRole::values())],
            'date' => ['required', 'date'],
            'attendance' => ['required', 'array'],
            'attendance.*.member_id' => ['required', 'integer', 'exists:members,id'],
            'attendance.*.present' => ['boolean'],
            'attendance.*.notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
