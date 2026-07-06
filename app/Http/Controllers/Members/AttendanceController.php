<?php

namespace App\Http\Controllers\Members;

use App\Enums\MemberRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Members\StoreAttendanceRequest;
use App\Models\Attendance;
use App\Models\Member;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $existing = Attendance::query()
            ->whereIn('member_id', $members->pluck('id'))
            ->whereDate('date', $date)
            ->whereNull('event_id')
            ->get()
            ->keyBy('member_id');

        return Inertia::render('Members/Attendance', [
            'branches' => $branches,
            'branch' => $branch,
            'date' => $date,
            'members' => $members->map(fn (Member $m) => [
                'id' => $m->id,
                'full_name' => $m->full_name,
                'present' => $existing->get($m->id)?->present ?? false,
            ]),
        ]);
    }

    public function store(StoreAttendanceRequest $request): RedirectResponse
    {
        $data = $request->validated();

        foreach ($data['attendance'] as $row) {
            Attendance::updateOrCreate(
                [
                    'member_id' => $row['member_id'],
                    'date' => $data['date'],
                    'event_id' => null,
                ],
                [
                    'present' => $row['present'] ?? false,
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
