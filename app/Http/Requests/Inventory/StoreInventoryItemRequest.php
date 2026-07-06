<?php

namespace App\Http\Requests\Inventory;

use App\Enums\InventoryCategory;
use App\Enums\ItemCondition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\InventoryItem::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(InventoryCategory::values())],
            'quantity' => ['required', 'integer', 'min:0'],
            'condition' => ['required', Rule::in(ItemCondition::values())],
            'location' => ['nullable', 'string', 'max:255'],
            'next_review_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'max:8192'],
        ];
    }
}
