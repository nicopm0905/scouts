<?php

namespace App\Http\Controllers\Plans;

use App\Enums\MemberRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Plans\StoreBranchPlanRequest;
use App\Http\Requests\Plans\UpdateBranchPlanRequest;
use App\Models\BranchPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class BranchPlanController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', BranchPlan::class);

        $user = $request->user();

        $plans = BranchPlan::query()
            ->with('objectives')
            ->when(! $user->canSeeAllBranches(), function ($query) use ($user) {
                $query->whereIn('branch', $user->branches ?? []);
            })
            ->orderByDesc('school_year')
            ->orderBy('branch')
            ->get()
            ->map(fn (BranchPlan $plan) => $this->planSummary($plan));

        return Inertia::render('BranchPlans/Index', [
            'plans' => $plans,
            'branches' => $this->branchOptions(),
            'canManage' => $user->can('create', BranchPlan::class),
        ]);
    }

    public function store(StoreBranchPlanRequest $request): RedirectResponse
    {
        BranchPlan::create($request->validated());

        return back()->with('success', 'Plan de rama creado correctamente.');
    }

    public function show(BranchPlan $branchPlan): Response
    {
        Gate::authorize('view', $branchPlan);

        $branchPlan->load(['objectives.activities']);

        return Inertia::render('BranchPlans/Show', [
            'plan' => [
                'id' => $branchPlan->id,
                'branch' => $branchPlan->branch->value,
                'branch_label' => $branchPlan->branch->label(),
                'school_year' => $branchPlan->school_year,
                'description' => $branchPlan->description,
                'completion_percentage' => $branchPlan->completionPercentage(),
                'objectives' => $branchPlan->objectives->map(fn ($o) => [
                    'id' => $o->id,
                    'description' => $o->description,
                    'term' => $o->term,
                    'status' => $o->status->value,
                    'status_label' => $o->status->label(),
                    'status_color' => $o->status->badgeColor(),
                    'position' => $o->position,
                    'activities' => $o->activities->map(fn ($a) => ['id' => $a->id, 'title' => $a->title]),
                ]),
            ],
            'canManage' => auth()->user()->can('update', $branchPlan),
        ]);
    }

    public function update(UpdateBranchPlanRequest $request, BranchPlan $branchPlan): RedirectResponse
    {
        $branchPlan->update($request->validated());

        return back()->with('success', 'Plan de rama actualizado correctamente.');
    }

    public function destroy(BranchPlan $branchPlan): RedirectResponse
    {
        Gate::authorize('delete', $branchPlan);

        $branchPlan->delete();

        return redirect()->route('branch-plans.index')->with('success', 'Plan de rama eliminado.');
    }

    private function planSummary(BranchPlan $plan): array
    {
        return [
            'id' => $plan->id,
            'branch' => $plan->branch->value,
            'branch_label' => $plan->branch->label(),
            'school_year' => $plan->school_year,
            'description' => $plan->description,
            'completion_percentage' => $plan->completionPercentage(),
            'objectives_count' => $plan->objectives->count(),
            'by_term' => collect([1, 2, 3])->mapWithKeys(function (int $term) use ($plan) {
                $objectives = $plan->objectives->where('term', $term);
                $total = $objectives->count();
                $done = $objectives->where('status', \App\Enums\ObjectiveStatus::Logrado)->count();

                return [$term => [
                    'total' => $total,
                    'done' => $done,
                    'percentage' => $total === 0 ? 0 : (int) round($done / $total * 100),
                ]];
            }),
        ];
    }

    private function branchOptions(): array
    {
        return collect(MemberRole::branches())->map(fn (MemberRole $b) => [
            'value' => $b->value,
            'label' => $b->label(),
        ])->all();
    }
}
