<?php

namespace App\Http\Controllers\Members;

use App\Enums\MemberRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Members\StoreAttendanceRequest;
use App\Models\Attendance;
use App\Models\Member;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('attendance.manage');

        $user = $request->user();
        $branches = $this->visibleBranches($user);

        $branch = $request->string('branch')->toString() ?: ($branches[0]['value'] ?? null);
        $date = $request->string('date')->toString() ?: now()->toDateString();

        $members = Member::query()
            ->visibleTo($user)
            ->active()
            ->when($branch, fn ($q) => $q->inBranch($branch))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $memberIds = $members->pluck('id');

        $existing = Attendance::query()
            ->whereIn('member_id', $memberIds)
            ->whereDate('date', $date)
            ->whereNull('event_id')
            ->get()
            ->keyBy('member_id');

        return Inertia::render('Members/Attendance', [
            'branches' => $branches,
            'branch' => $branch,
            'branchExplicit' => $request->has('branch'),
            'date' => $date,
            'members' => $members->map(fn (Member $m) => [
                'id' => $m->id,
                'full_name' => $m->full_name,
                'present' => $existing->get($m->id)?->present ?? false,
                'notes' => $existing->get($m->id)?->notes,
            ]),
            'stats' => $this->quarterStats($memberIds->all()),
        ]);
    }

    /**
     * Resumen de asistencia de la rama en el trimestre en curso (reuniones sueltas,
     * sin contar eventos): número de sesiones y % medio de presencia.
     *
     * @param  array<int, int>  $memberIds
     * @return array{sessions:int, avg_present:int|null}
     */
    private function quarterStats(array $memberIds): array
    {
        if (empty($memberIds)) {
            return ['sessions' => 0, 'avg_present' => null];
        }

        $rows = Attendance::query()
            ->whereIn('member_id', $memberIds)
            ->whereNull('event_id')
            ->where('date', '>=', now()->firstOfQuarter()->toDateString())
            ->selectRaw('date, count(*) as total, sum(case when present then 1 else 0 end) as present_count')
            ->groupBy('date')
            ->get();

        return [
            'sessions' => $rows->count(),
            'avg_present' => $rows->isEmpty()
                ? null
                : (int) round($rows->avg(fn ($r) => $r->present_count / max($r->total, 1)) * 100),
        ];
    }

    public function store(StoreAttendanceRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Anti-IDOR: solo se puede escribir asistencia de miembros visibles para el usuario
        // (un responsable no puede tocar la asistencia de otra rama).
        $requestedIds = collect($data['attendance'])->pluck('member_id')->unique();
        $allowedIds = Member::query()
            ->visibleTo($request->user())
            ->whereIn('id', $requestedIds)
            ->pluck('id');

        abort_if($requestedIds->diff($allowedIds)->isNotEmpty(), 403, 'No puedes registrar asistencia de miembros fuera de tus ramas.');

        // Normaliza a fecha sin hora para que coincida con el formato almacenado (cast DateOnly).
        $date = Carbon::parse($data['date'])->toDateString();

        foreach ($data['attendance'] as $row) {
            Attendance::updateOrCreate(
                [
                    'member_id' => $row['member_id'],
                    'date' => $date,
                    'event_id' => null,
                ],
                [
                    'present' => $row['present'] ?? false,
                    'notes' => ($row['notes'] ?? null) !== null && trim($row['notes']) !== '' ? trim($row['notes']) : null,
                    'recorded_by' => $request->user()->id,
                ]
            );
        }

        return back()->with('success', 'Asistencia guardada.');
    }

    private function visibleBranches($user): array
    {
        $branches = $user->canSeeAllBranches()
            ? MemberRole::branches()
            : collect(MemberRole::branches())->filter(fn (MemberRole $b) => in_array($b->value, $user->branches ?? [], true))->values()->all();

        return collect($branches)->map(fn (MemberRole $b) => ['value' => $b->value, 'label' => $b->label()])->all();
    }
}
