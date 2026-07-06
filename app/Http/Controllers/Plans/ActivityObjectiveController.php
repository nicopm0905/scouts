<?php

namespace App\Http\Controllers\Plans;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\BranchPlanObjective;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/** Vincula/desvincula una actividad con un objetivo del plan de rama (pivote activity_objective). */
class ActivityObjectiveController extends Controller
{
    public function store(Activity $activity, BranchPlanObjective $objective): RedirectResponse
    {
        Gate::authorize('update', $activity);
        Gate::authorize('update', $objective->branchPlan);

        $activity->objectives()->syncWithoutDetaching([$objective->id]);

        return back()->with('success', 'Actividad vinculada al objetivo.');
    }

    public function destroy(Activity $activity, BranchPlanObjective $objective): RedirectResponse
    {
        Gate::authorize('update', $activity);
        Gate::authorize('update', $objective->branchPlan);

        $activity->objectives()->detach($objective->id);

        return back()->with('success', 'Vínculo eliminado.');
    }
}
