<?php

namespace App\Policies;

use App\Models\Member;
use App\Models\User;
use App\Support\ResolvesBranchScope;

class MemberPolicy
{
    use ResolvesBranchScope;

    public function viewAny(User $user): bool
    {
        return $user->can('members.view');
    }

    public function view(User $user, Member $member): bool
    {
        return $user->can('members.view') && $this->memberInScope($user, $member);
    }

    /** Acceso a datos sensibles (ficha médica, consentimientos). Nunca para familias. */
    public function viewSensitive(User $user, Member $member): bool
    {
        return $user->can('members.sensitive') && $this->memberInScope($user, $member);
    }

    public function create(User $user): bool
    {
        return $user->can('members.manage');
    }

    public function update(User $user, Member $member): bool
    {
        return $user->can('members.manage') && $this->memberInScope($user, $member);
    }

    public function delete(User $user, Member $member): bool
    {
        return $user->can('members.manage') && $this->memberInScope($user, $member);
    }
}
