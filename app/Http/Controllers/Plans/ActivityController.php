<?php

namespace App\Http\Controllers\Plans;

use App\Enums\MemberRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Plans\StoreActivityRequest;
use App\Http\Requests\Plans\UpdateActivityRequest;
use App\Models\Activity;
use App\Models\BranchPlanObjective;
use App\Models\Event;
use App\Models\InventoryItem;
use App\Services\Drive\DriveServiceInterface;
use App\Services\Plans\ActivityDuplicator;
use App\Support\MscPlanCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ActivityController extends Controller
{
    public function __construct(private readonly DriveServiceInterface $drive) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Activity::class);

        $activities = Activity::query()
            ->with('materials')
            ->when($request->filled('branch'), fn ($q) => $q->where('branch', $request->string('branch')))
            ->when($request->filled('min_duration'), fn ($q) => $q->where('duration_minutes', '>=', (int) $request->input('min_duration')))
            ->when($request->filled('max_duration'), fn ($q) => $q->where('duration_minutes', '<=', (int) $request->input('max_duration')))
            ->when($request->filled('material'), function ($q) use ($request) {
                $term = $request->string('material');
                $q->whereHas('materials', fn ($m) => $m->where('name', 'like', "%{$term}%"));
            })
            ->orderBy('title')
            ->get()
            ->map(fn (Activity $activity) => [
                'id' => $activity->id,
                'title' => $activity->title,
                'branch' => $activity->branch,
                'branch_label' => $activity->branch ? MemberRole::from($activity->branch)->label() : 'Todas',
                'duration_minutes' => $activity->duration_minutes,
                'materials' => $activity->materials->pluck('name'),
            ]);

        return Inertia::render('Activities/Index', [
            'activities' => $activities,
            'branches' => $this->branchOptions(),
            'filters' => $request->only(['branch', 'min_duration', 'max_duration', 'material']),
            'canManage' => $request->user()->can('create', Activity::class),
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', Activity::class);

        return Inertia::render('Activities/Create', [
            'branches' => $this->branchOptions(),
            'inventoryItems' => $this->inventoryOptions(),
            'catalog' => MscPlanCatalog::forFrontend(),
        ]);
    }

    public function store(StoreActivityRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['attachments', 'materials']);
        $data['created_by'] = $request->user()->id;
        $data['attachment_file_ids'] = $this->uploadAttachments($request);

        $activity = Activity::create($data);

        $this->syncMaterials($activity, $request->input('materials', []));

        return redirect()->route('activities.show', $activity)->with('success', 'Actividad creada correctamente.');
    }

    public function show(Activity $activity): Response
    {
        Gate::authorize('view', $activity);

        $activity->load(['materials.inventoryItem', 'objectives.branchPlan', 'events']);
        $user = auth()->user();

        return Inertia::render('Activities/Show', [
            'activity' => $this->activityDetail($activity),
            'canManage' => $user->can('update', $activity),
            'availableEvents' => Event::query()
                ->orderBy('start_at')
                ->get(['id', 'title', 'start_at'])
                ->map(fn ($e) => ['id' => $e->id, 'title' => $e->title, 'start_at' => $e->start_at]),
            'availableObjectives' => BranchPlanObjective::query()
                ->with('branchPlan')
                ->whereHas('branchPlan', function ($q) use ($user) {
                    if (! $user->canSeeAllBranches()) {
                        $q->whereIn('branch', $user->branches ?? []);
                    }
                })
                ->get()
                ->map(fn ($o) => [
                    'id' => $o->id,
                    'label' => ($o->branchPlan?->branch->label() ?? '').' '.($o->branchPlan?->school_year ?? '').' — '.$o->description,
                ]),
        ]);
    }

    public function edit(Activity $activity): Response
    {
        Gate::authorize('update', $activity);

        $activity->load('materials');

        return Inertia::render('Activities/Edit', [
            'activity' => $this->activityDetail($activity),
            'branches' => $this->branchOptions(),
            'inventoryItems' => $this->inventoryOptions(),
            'catalog' => MscPlanCatalog::forFrontend(),
        ]);
    }

    public function update(UpdateActivityRequest $request, Activity $activity): RedirectResponse
    {
        $data = $request->safe()->except(['attachments', 'materials', 'removed_attachment_ids']);

        $existingIds = collect($activity->attachment_file_ids ?? []);
        $removedIds = collect($request->input('removed_attachment_ids', []));
        $keptIds = $existingIds->diff($removedIds);
        $newIds = collect($this->uploadAttachments($request));

        $data['attachment_file_ids'] = $keptIds->merge($newIds)->values()->all();

        $activity->update($data);

        $this->syncMaterials($activity, $request->input('materials', []));

        return redirect()->route('activities.show', $activity)->with('success', 'Actividad actualizada correctamente.');
    }

    public function destroy(Activity $activity): RedirectResponse
    {
        Gate::authorize('delete', $activity);

        $activity->delete();

        return redirect()->route('activities.index')->with('success', 'Actividad eliminada.');
    }

    public function duplicate(Request $request, Activity $activity, ActivityDuplicator $duplicator): RedirectResponse
    {
        Gate::authorize('create', Activity::class);

        $copy = $duplicator->duplicate($activity, $request->user()->id);

        return redirect()->route('activities.edit', $copy)->with('success', 'Actividad duplicada correctamente.');
    }

    private function uploadAttachments(Request $request): array
    {
        $ids = [];
        foreach ($request->file('attachments', []) as $file) {
            $ids[] = $this->drive->upload($file)->id;
        }

        return $ids;
    }

    private function syncMaterials(Activity $activity, array $materials): void
    {
        $activity->materials()->delete();

        foreach ($materials as $material) {
            if (empty($material['name'])) {
                continue;
            }

            $activity->materials()->create([
                'name' => $material['name'],
                'quantity' => $material['quantity'] ?? 1,
                'inventory_item_id' => $material['inventory_item_id'] ?? null,
            ]);
        }
    }

    private function activityDetail(Activity $activity): array
    {
        return [
            'id' => $activity->id,
            'title' => $activity->title,
            'branch' => $activity->branch,
            'branch_label' => $activity->branch ? MemberRole::from($activity->branch)->label() : 'Todas',
            'activity_type' => $activity->activity_type?->value,
            'activity_type_label' => $activity->activity_type?->label(),
            'owner' => $activity->owner,
            'scheduled_date' => $activity->scheduled_date?->toDateString(),
            'place' => $activity->place,
            'evaluation' => $activity->evaluation,
            'duration_minutes' => $activity->duration_minutes,
            'day_number' => $activity->day_number,
            'time_slot' => $activity->time_slot,
            'activity_number' => $activity->activity_number,
            'objectives_text' => $activity->objectives_text,
            'development' => $activity->development,
            'materials_text' => $activity->materials_text,
            'attachment_file_ids' => $activity->attachment_file_ids ?? [],
            'attachments' => collect($activity->attachment_file_ids ?? [])->map(fn ($id) => [
                'id' => $id,
                'web_view_link' => $this->drive->webViewLink($id),
            ]),
            'materials' => $activity->materials->map(fn ($m) => [
                'id' => $m->id,
                'name' => $m->name,
                'quantity' => $m->quantity,
                'inventory_item_id' => $m->inventory_item_id,
                'inventory_item_name' => $m->inventoryItem?->name,
            ]),
            'objectives' => $activity->relationLoaded('objectives') ? $activity->objectives->map(fn ($o) => [
                'id' => $o->id,
                'description' => $o->description,
                'branch_plan_id' => $o->branch_plan_id,
                'plan_label' => $o->branchPlan ? $o->branchPlan->branch->label().' '.$o->branchPlan->school_year : null,
            ]) : [],
            'events' => $activity->relationLoaded('events') ? $activity->events->map(fn ($e) => [
                'id' => $e->id,
                'title' => $e->title,
                'start_at' => $e->start_at,
            ]) : [],
        ];
    }

    private function branchOptions(): array
    {
        return collect(MemberRole::branches())->map(fn (MemberRole $b) => [
            'value' => $b->value,
            'label' => $b->label(),
        ])->all();
    }

    private function inventoryOptions(): array
    {
        return InventoryItem::query()->orderBy('name')->get()->map(fn ($i) => [
            'value' => $i->id,
            'label' => $i->name,
        ])->all();
    }
}
