<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

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
     * Roles que ven todas las ramas sin restricción.
     * responsable queda limitado a $this->branches.
     */
    public function canSeeAllBranches(): bool
    {
        return $this->hasAnyRole([
            UserRole::Admin->value,
            UserRole::Secretaria->value,
            UserRole::Tesoreria->value,
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
}
