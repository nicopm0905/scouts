<?php

namespace App\Models;

use App\Enums\MemberRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Member extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'first_name', 'last_name', 'phone', 'email', 'role',
        'birth_date', 'active', 'joined_at', 'user_id', 'notes',
    ];

    protected $casts = [
        'role' => MemberRole::class,
        'birth_date' => 'date',
        'joined_at' => 'date',
        'active' => 'boolean',
    ];

    // --- Relaciones ---

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function healthRecord(): HasOne
    {
        return $this->hasOne(HealthRecord::class);
    }

    public function consents(): HasMany
    {
        return $this->hasMany(Consent::class);
    }

    public function leaderProfile(): HasOne
    {
        return $this->hasOne(LeaderProfile::class);
    }

    public function families(): BelongsToMany
    {
        return $this->belongsToMany(Family::class)
            ->withPivot('relationship')
            ->withTimestamps();
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function charges(): BelongsToMany
    {
        return $this->belongsToMany(Charge::class)
            ->withPivot(['amount', 'status', 'paid_at', 'payment_method', 'notes', 'reminder_sent_at'])
            ->withTimestamps();
    }

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class)
            ->withPivot(['enrolled', 'public_token', 'confirmed_at', 'authorization_file_id', 'notes'])
            ->withTimestamps();
    }

    // --- Accessors / helpers ---

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getAgeAttribute(): ?int
    {
        return $this->birth_date?->age;
    }

    public function isLeader(): bool
    {
        return $this->role === MemberRole::Responsable;
    }

    // --- Scopes ---

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function scopeInBranch(Builder $query, string|MemberRole $branch): Builder
    {
        return $query->where('role', $branch instanceof MemberRole ? $branch->value : $branch);
    }

    /**
     * Limita la consulta a lo que un usuario puede ver.
     * admin/secretaria/tesoreria ven todo; responsable solo sus ramas.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->canSeeAllBranches()) {
            return $query;
        }

        $branches = $user->branches ?? [];

        // Un responsable ve a los miembros de sus ramas y a sí mismo/otros responsables de su rama.
        return $query->whereIn('role', $branches ?: ['__none__']);
    }
}
