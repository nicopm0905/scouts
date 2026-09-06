<?php

namespace App\Support;

use App\Enums\MemberRole;
use App\Models\Member;
use App\Models\User;

/**
 * Lógica compartida de alcance para las policies. Tres modos:
 *  - global   : coordinación, secretaría, tesorería, intendencia → sin filtro.
 *  - por rama : responsable → limitado a User::branches.
 *  - familia  : limitado a los scouts a cargo de la cuenta (User::children()).
 */
trait ResolvesBranchScope
{
    /** ¿El usuario gestiona (o pertenece a) la rama indicada? */
    protected function branchInScope(User $user, string|MemberRole|null $branch): bool
    {
        if ($user->canSeeAllBranches()) {
            return true;
        }

        $branch = $branch instanceof MemberRole ? $branch->value : $branch;

        return $branch !== null && in_array($branch, $user->branches ?? [], true);
    }

    /** ¿El miembro cae dentro del alcance del usuario (rama o parentesco)? */
    protected function memberInScope(User $user, Member $member): bool
    {
        if ($user->canSeeAllBranches()) {
            return true;
        }

        if ($user->isFamilia()) {
            return in_array($member->id, $user->childIds(), true);
        }

        return $this->branchInScope($user, $member->role);
    }
}
