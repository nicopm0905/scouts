<?php

namespace App\Policies;

use App\Enums\MemberRole;
use App\Models\Member;
use App\Models\User;

class MemberPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('members.view');
    }

    public function view(User $user, Member $member): bool
    {
        return $user->can('members.view') && $this->inScope($user, $member);
    }

    /** Acceso a datos sensibles (ficha médica, consentimientos). */
    public function viewSensitive(User $user, Member $member): bool
    {
        return $user->can('members.sensitive') && $this->inScope($user, $member);
    }

    public function create(User $user): bool
    {
        return $user->can('members.manage');
    }

    public function update(User $user, Member $member): bool
    {
        return $user->can('members.manage') && $this->inScope($user, $member);
    }

    public function delete(User $user, Member $member): bool
    {
        return $user->can('members.manage') && $this->inScope($user, $member);
    }

    /**
     * Un responsable solo puede tocar miembros de sus ramas.
     * Los roles con visibilidad global (admin/secretaría/tesorería) no tienen restricción.
     */
    private function inScope(User $user, Member $member): bool
    {
        if ($user->canSeeAllBranches()) {
            return true;
        }

        $branch = $member->role instanceof MemberRole ? $member->role->value : $member->role;

        return in_array($branch, $user->branches ?? [], true);
    }
}
