<?php

namespace App\Http\Requests\Plans;

use App\Enums\ActivityType;
use App\Enums\MemberRole;
use App\Models\Activity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Activity::class);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'branch' => ['nullable', Rule::in(MemberRole::values())],
            'activity_type' => ['nullable', Rule::in(ActivityType::values())],
            'owner' => ['nullable', 'string', 'max:120'],
            'scheduled_date' => ['nullable', 'date'],
            'place' => ['nullable', 'string', 'max:255'],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'day_number' => ['nullable', 'string', 'max:50'],
            'time_slot' => ['nullable', 'string', 'max:50'],
            'activity_number' => ['nullable', 'integer', 'min:1'],
            'objectives_text' => ['nullable', 'string'],
            'development' => ['nullable', 'string'],
            'materials_text' => ['nullable', 'string'],
            'evaluation' => ['nullable', 'string'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:20480'],
            'materials' => ['nullable', 'array'],
            'materials.*.name' => ['required_with:materials', 'string', 'max:255'],
            'materials.*.quantity' => ['nullable', 'integer', 'min:1'],
            'materials.*.inventory_item_id' => ['nullable', 'exists:inventory_items,id'],
        ];
    }
}
