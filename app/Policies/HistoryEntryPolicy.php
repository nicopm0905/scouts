<?php

namespace App\Policies;

use App\Models\HistoryEntry;
use App\Models\User;

class HistoryEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // la historia es de lectura general para usuarios autenticados
    }

    public function view(User $user, HistoryEntry $entry): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->can('history.manage');
    }

    public function update(User $user, HistoryEntry $entry): bool
    {
        return $user->can('history.manage');
    }

    public function delete(User $user, HistoryEntry $entry): bool
    {
        return $user->can('history.manage');
    }
}
