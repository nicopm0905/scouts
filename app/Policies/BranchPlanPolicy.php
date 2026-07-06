<?php

namespace App\Policies;

use App\Enums\MemberRole;
use App\Models\BranchPlan;
use App\Models\User;

class BranchPlanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('plans.view');
    }

    public function view(User $user, BranchPlan $plan): bool
    {
        return $user->can('plans.view') && $this->inScope($user, $plan);
    }

    public function create(User $user): bool
    {
        return $user->can('plans.manage');
    }

    public function update(User $user, BranchPlan $plan): bool
    {
        return $user->can('plans.manage') && $this->inScope($user, $plan);
    }

    public function delete(User $user, BranchPlan $plan): bool
    {
        return $user->can('plans.manage') && $this->inScope($user, $plan);
    }

    private function inScope(User $user, BranchPlan $plan): bool
    {
        if ($user->canSeeAllBranches()) {
            return true;
        }

        $branch = $plan->branch instanceof MemberRole ? $plan->branch->value : $plan->branch;

        return in_array($branch, $user->branches ?? [], true);
    }
}
