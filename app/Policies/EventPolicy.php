<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('events.view');
    }

    public function view(User $user, Event $event): bool
    {
        return $user->can('events.view');
    }

    public function create(User $user): bool
    {
        return $user->can('events.manage');
    }

    public function update(User $user, Event $event): bool
    {
        return $user->can('events.manage') && $this->inScope($user, $event);
    }

    public function delete(User $user, Event $event): bool
    {
        return $user->can('events.manage') && $this->inScope($user, $event);
    }

    /** Un responsable solo gestiona eventos que afectan a alguna de sus ramas. */
    private function inScope(User $user, Event $event): bool
    {
        if ($user->canSeeAllBranches()) {
            return true;
        }

        $eventBranches = $event->branches ?? [];

        // Sin ramas asignadas = evento de grupo: solo lo tocan roles globales.
        return (bool) array_intersect($eventBranches, $user->branches ?? []);
    }
}
