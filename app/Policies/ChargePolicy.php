<?php

namespace App\Policies;

use App\Models\Charge;
use App\Models\User;

class ChargePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('charges.view');
    }

    public function view(User $user, Charge $charge): bool
    {
        return $user->can('charges.view');
    }

    public function create(User $user): bool
    {
        return $user->can('charges.manage');
    }

    public function update(User $user, Charge $charge): bool
    {
        return $user->can('charges.manage');
    }

    public function delete(User $user, Charge $charge): bool
    {
        return $user->can('charges.manage');
    }
}
