<?php

namespace App\Services\Reports;

use App\Enums\MemberRole;
use App\Enums\ObjectiveStatus;
use App\Models\Attendance;
use App\Models\BranchPlan;
use App\Models\Event;
use App\Models\Member;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Memoria del curso: junta el progreso de los planes de rama, los eventos
 * realizados, la asistencia media y el censo, para el cierre de curso.
 */
class AnnualReportService
{
    /**
     * @param  list<string>|null  $branches  null = todas las ramas
     * @return array<string, mixed>
     */
    public function build(string $schoolYear, ?array $branches = null): array
    {
        [$from, $to] = $this->range($schoolYear);
        $cutoff = min($to, Carbon::now());

        $plans = BranchPlan::query()
            ->where('school_year', $schoolYear)
            ->when($branches, fn ($q) => $q->whereIn('branch', $branches))
            ->with('objectives')
            ->orderBy('branch')
            ->get();

        $events = Event::query()
            ->whereBetween('start_at', [$from, $cutoff])
            ->when($branches, function ($q) use ($branches) {
                $q->where(function ($sub) use ($branches) {
                    foreach ($branches as $b) {
                        $sub->orWhereJsonContains('branches', $b);
                    }
                });
            })
            ->orderBy('start_at')
            ->get(['id', 'title', 'type', 'start_at', 'branches']);

        return [
            'school_year' => $schoolYear,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'group_name' => config('group.name'),
            'generated_at' => Carbon::now()->format('d/m/Y'),
            'plans' => $plans->map(fn (BranchPlan $plan) => $this->planBlock($plan, $events, $from, $cutoff))->all(),
            'events_by_type' => $events
                ->groupBy(fn (Event $e) => $e->type->label())
                ->map->count()
                ->all(),
            'events_total' => $events->count(),
            'census' => $this->census($branches),
            'retention' => $this->retention($branches, $from, $cutoff),
        ];
    }

    /**
     * Altas y bajas del curso (para medir retención). Una "baja" es un miembro
     * cuyo `left_at` cae en el periodo (lo rellena el modelo al desactivarlo).
     *
     * @param  list<string>|null  $branches
     * @return array{altas:int, bajas:int, net:int}
     */
    private function retention(?array $branches, Carbon $from, Carbon $cutoff): array
    {
        $inRange = fn ($query, string $column) => $query
            ->whereDate($column, '>=', $from->toDateString())
            ->whereDate($column, '<=', $cutoff->toDateString());

        $altas = $inRange(
            Member::query()->when($branches, fn ($q) => $q->whereIn('role', $branches)),
            'joined_at'
        )->count();

        $bajas = $inRange(
            Member::withTrashed()->when($branches, fn ($q) => $q->whereIn('role', $branches)),
            'left_at'
        )->count();

        return ['altas' => $altas, 'bajas' => $bajas, 'net' => $altas - $bajas];
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function range(string $schoolYear): array
    {
        [$startYear] = explode('-', $schoolYear);
        $start = Carbon::create((int) $startYear, 9, 1)->startOfDay();

        return [$start, $start->copy()->addYear()->subDay()->endOfDay()];
    }

    /**
     * @param  Collection<int, Event>  $allEvents
     * @return array<string, mixed>
     */
    private function planBlock(BranchPlan $plan, $allEvents, Carbon $from, Carbon $cutoff): array
    {
        $branch = $plan->branch->value;
        $objectives = $plan->objectives;

        $memberIds = Member::query()->active()->inBranch($branch)->pluck('id');

        $sessions = Attendance::query()
            ->whereIn('member_id', $memberIds)
            ->whereNull('event_id')
            ->whereBetween('date', [$from->toDateString(), $cutoff->toDateString()])
            ->selectRaw('date, count(*) as total, sum(case when present then 1 else 0 end) as present_count')
            ->groupBy('date')
            ->get();

        $branchEvents = $allEvents->filter(fn (Event $e) => in_array($branch, $e->branches ?? [], true));

        return [
            'branch' => $branch,
            'branch_label' => $plan->branch->label(),
            'description' => $plan->description,
            'objectives' => [
                'total' => $objectives->count(),
                'logrado' => $objectives->where('status', ObjectiveStatus::Logrado)->count(),
                'en_curso' => $objectives->where('status', ObjectiveStatus::EnCurso)->count(),
                'pendiente' => $objectives->where('status', ObjectiveStatus::Pendiente)->count(),
                'percentage' => $plan->completionPercentage(),
                'by_area' => $objectives
                    ->groupBy('development_area')
                    ->map(fn ($group, $area) => [
                        'area' => $area ?: 'Sin ámbito',
                        'total' => $group->count(),
                        'logrado' => $group->where('status', ObjectiveStatus::Logrado)->count(),
                    ])
                    ->values()
                    ->all(),
            ],
            'attendance' => [
                'sessions' => $sessions->count(),
                'avg_present' => $sessions->isEmpty()
                    ? null
                    : (int) round($sessions->avg(fn ($r) => $r->present_count / max($r->total, 1)) * 100),
            ],
            'events_count' => $branchEvents->count(),
            'events' => $branchEvents
                ->sortBy('start_at')
                ->map(fn (Event $e) => [
                    'title' => $e->title,
                    'type' => $e->type->label(),
                    'date' => $e->start_at?->format('d/m/Y'),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  list<string>|null  $branches
     * @return array<int, array{branch:string, label:string, count:int}>
     */
    private function census(?array $branches): array
    {
        $counts = Member::query()
            ->active()
            ->when($branches, fn ($q) => $q->whereIn('role', $branches))
            ->selectRaw('role, count(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role');

        return collect(MemberRole::cases())
            ->map(fn (MemberRole $r) => [
                'branch' => $r->value,
                'label' => $r->label(),
                'count' => (int) ($counts[$r->value] ?? 0),
            ])
            ->filter(fn (array $row) => $row['count'] > 0)
            ->values()
            ->all();
    }
}
