<?php

namespace App\Models;

use App\Enums\InventoryCategory;
use App\Enums\ItemCondition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'category', 'quantity', 'condition',
        'location', 'next_review_at', 'photo_file_id', 'notes',
    ];

    protected $casts = [
        'category' => InventoryCategory::class,
        'condition' => ItemCondition::class,
        'quantity' => 'integer',
        'next_review_at' => 'date',
    ];

    public function checkouts(): HasMany
    {
        return $this->hasMany(Checkout::class);
    }

    /** Unidades actualmente prestadas (checkouts sin devolver). */
    public function checkedOutQuantity(): int
    {
        return (int) $this->checkouts()->whereNull('returned_at')->sum('quantity');
    }

    public function availableQuantity(): int
    {
        return max(0, $this->quantity - $this->checkedOutQuantity());
    }

    public function scopeNeedingReview(Builder $query, int $days = 30): Builder
    {
        return $query->whereNotNull('next_review_at')
            ->where('next_review_at', '<=', now()->addDays($days));
    }
}
