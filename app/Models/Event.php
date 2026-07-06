<?php

namespace App\Models;

use App\Enums\EventType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'title', 'type', 'start_at', 'end_at', 'location',
        'description', 'branches', 'created_by',
    ];

    protected $casts = [
        'type' => EventType::class,
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'branches' => 'array',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Miembros inscritos (pivote event_member). */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Member::class)
            ->withPivot(['enrolled', 'public_token', 'confirmed_at', 'authorization_file_id', 'notes'])
            ->withTimestamps();
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(EventMember::class);
    }

    public function checklistItems(): HasMany
    {
        return $this->hasMany(EventChecklistItem::class);
    }

    public function charges(): HasMany
    {
        return $this->hasMany(Charge::class);
    }

    public function activities(): BelongsToMany
    {
        return $this->belongsToMany(Activity::class)->withTimestamps();
    }

    public function albums(): HasMany
    {
        return $this->hasMany(Album::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function requiresEnrollment(): bool
    {
        return $this->type->requiresEnrollment();
    }
}
