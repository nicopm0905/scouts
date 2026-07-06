<?php

namespace App\Models;

use App\Enums\ChargeType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Charge extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'description', 'amount', 'due_date', 'type',
        'event_id', 'target_branches', 'sibling_discount_applies', 'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date',
        'type' => ChargeType::class,
        'target_branches' => 'array',
        'sibling_discount_applies' => 'boolean',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Member::class)
            ->withPivot(['amount', 'status', 'paid_at', 'payment_method', 'notes', 'reminder_sent_at'])
            ->withTimestamps();
    }

    /** Filas de reparto (charge_member) como modelos, para operar sobre el estado. */
    public function assignments(): HasMany
    {
        return $this->hasMany(ChargeMember::class);
    }
}
