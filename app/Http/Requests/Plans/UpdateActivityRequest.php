<?php

namespace App\Http\Requests\Plans;

use App\Enums\MemberRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('activity'));
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'branch' => ['nullable', Rule::in(MemberRole::values())],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'day_number' => ['nullable', 'string', 'max:50'],
            'time_slot' => ['nullable', 'string', 'max:50'],
            'activity_number' => ['nullable', 'integer', 'min:1'],
            'objectives_text' => ['nullable', 'string'],
            'development' => ['nullable', 'string'],
            'materials_text' => ['nullable', 'string'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:20480'],
            'removed_attachment_ids' => ['nullable', 'array'],
            'removed_attachment_ids.*' => ['string'],
            'materials' => ['nullable', 'array'],
            'materials.*.name' => ['required_with:materials', 'string', 'max:255'],
            'materials.*.quantity' => ['nullable', 'integer', 'min:1'],
            'materials.*.inventory_item_id' => ['nullable', 'exists:inventory_items,id'],
        ];
    }
}
