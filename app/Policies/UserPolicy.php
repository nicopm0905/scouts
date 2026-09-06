<?php

namespace App\Policies;

use App\Models\User;

/**
 * Gestión de cuentas de acceso: solo coordinación (permiso 'users.manage', que
 * el admin recibe vía Gate::before). Las salvaguardas de "no dejar el sistema
 * sin admin" y "no bloquearse a uno mismo" viven en el controlador, porque
 * Gate::before haría que el admin se saltase cualquier comprobación aquí.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('users.manage');
    }

    public function update(User $actor, User $target): bool
    {
        return $actor->can('users.manage');
    }
}
