<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Checkout extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_item_id', 'quantity', 'event_id', 'member_id',
        'checked_out_at', 'expected_return_at', 'returned_at', 'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'checked_out_at' => 'date',
        'expected_return_at' => 'date',
        'returned_at' => 'date',
    ];

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function isOverdue(): bool
    {
        return $this->returned_at === null
            && $this->expected_return_at !== null
            && $this->expected_return_at->isPast();
    }

    public function scopeOutstanding(Builder $query): Builder
    {
        return $query->whereNull('returned_at');
    }
}
