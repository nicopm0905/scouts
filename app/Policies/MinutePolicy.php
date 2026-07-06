<?php

namespace App\Policies;

use App\Models\Minute;
use App\Models\User;

class MinutePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('minutes.view');
    }

    public function view(User $user, Minute $minute): bool
    {
        return $user->can('minutes.view');
    }

    public function create(User $user): bool
    {
        return $user->can('minutes.manage');
    }

    public function update(User $user, Minute $minute): bool
    {
        return $user->can('minutes.manage');
    }

    public function delete(User $user, Minute $minute): bool
    {
        return $user->can('minutes.manage');
    }
}
