<?php

namespace App\Models;

use App\Enums\FamilyRelationship;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'branches',
        'active',
        'ical_token',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'branches' => 'array',
            'active' => 'boolean',
        ];
    }

    /** Ficha de miembro vinculada (si el usuario es también un responsable/miembro). */
    public function member(): HasOne
    {
        return $this->hasOne(Member::class);
    }

    /**
     * Núcleos familiares a los que pertenece una cuenta de tipo "familia".
     * N:M: una cuenta puede estar ligada a varias familias (custodia compartida).
     */
    public function families(): BelongsToMany
    {
        return $this->belongsToMany(Family::class)->withTimestamps();
    }

    /** IDs de las familias de la cuenta (para acotar consultas). */
    public function familyIds(): array
    {
        return $this->families()->pluck('families.id')->all();
    }

    /**
     * Scouts a cargo de esta cuenta: los miembros vinculados a sus familias con
     * parentesco de "hermano/a" (la convención del modelo para el niño scout,
     * frente a padre/madre/tutor que son adultos de contacto).
     *
     * @return Collection<int, Member>
     */
    public function children()
    {
        $familyIds = $this->familyIds();

        if (empty($familyIds)) {
            return collect();
        }

        return Member::query()
            ->whereHas('families', fn ($q) => $q
                ->whereIn('families.id', $familyIds)
                ->where('family_member.relationship', FamilyRelationship::Hermano->value))
            ->get();
    }

    /** IDs de los scouts a cargo de esta cuenta. */
    public function childIds(): array
    {
        return $this->children()->pluck('id')->all();
    }

    public function isFamilia(): bool
    {
        return $this->hasRole(UserRole::Familia->value);
    }

    public function isIntendente(): bool
    {
        return $this->hasRole(UserRole::Intendencia->value);
    }

    /**
     * Roles que ven todas las ramas sin restricción.
     * responsable queda limitado a $this->branches.
     */
    public function canSeeAllBranches(): bool
    {
        return $this->hasAnyRole([
            UserRole::Admin->value,
            UserRole::Secretaria->value,
            UserRole::Tesoreria->value,
            UserRole::Intendencia->value,
        ]);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(UserRole::Admin->value);
    }

    /** ¿Puede el usuario gestionar la rama indicada? */
    public function managesBranch(string $branch): bool
    {
        return $this->canSeeAllBranches() || in_array($branch, $this->branches ?? [], true);
    }

    /**
     * Token personal para suscribirse en modo solo lectura al calendario de eventos
     * (Agente C). Se genera perezosamente la primera vez que se necesita.
     */
    public function ensureIcalToken(): string
    {
        if (empty($this->ical_token)) {
            $this->forceFill(['ical_token' => Str::random(48)])->save();
        }

        return $this->ical_token;
    }
}
