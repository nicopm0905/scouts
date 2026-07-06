<?php

namespace App\Http\Controllers\Plans;

use App\Http\Controllers\Controller;
use App\Http\Requests\Plans\StoreObjectiveRequest;
use App\Http\Requests\Plans\UpdateObjectiveRequest;
use App\Models\BranchPlan;
use App\Models\BranchPlanObjective;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class BranchPlanObjectiveController extends Controller
{
    public function store(StoreObjectiveRequest $request, BranchPlan $branchPlan): RedirectResponse
    {
        $branchPlan->objectives()->create($request->validated());

        return back()->with('success', 'Objetivo añadido correctamente.');
    }

    public function update(UpdateObjectiveRequest $request, BranchPlanObjective $objective): RedirectResponse
    {
        $objective->update($request->validated());

        return back()->with('success', 'Objetivo actualizado correctamente.');
    }

    public function destroy(BranchPlanObjective $objective): RedirectResponse
    {
        Gate::authorize('update', $objective->branchPlan);

        $objective->delete();

        return back()->with('success', 'Objetivo eliminado.');
    }
}
