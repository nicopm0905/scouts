<?php

namespace App\Http\Controllers\Events;

use App\Http\Controllers\Controller;
use App\Http\Requests\Events\UpdateEnrollmentRequest;
use App\Models\Event;
use App\Models\EventMember;
use App\Models\Member;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** Gestión (por el equipo, no por las familias) de las inscripciones de un evento. */
class EventEnrollmentController extends Controller
{
    use AuthorizesRequests;

    public function store(Request $request, Event $event): RedirectResponse
    {
        $this->authorize('update', $event);

        $validated = $request->validate([
            'member_id' => ['required', 'exists:members,id'],
        ]);

        $member = Member::findOrFail($validated['member_id']);

        $enrollment = EventMember::firstOrCreate(
            ['event_id' => $event->id, 'member_id' => $member->id],
            ['enrolled' => true, 'public_token' => Str::random(48)]
        );

        if (! $enrollment->wasRecentlyCreated) {
            $enrollment->update(['enrolled' => true]);
        }

        return back()->with('success', "{$member->full_name} añadido/a a la actividad.");
    }

    public function update(UpdateEnrollmentRequest $request, Event $event, EventMember $enrollment): RedirectResponse
    {
        $this->authorize('update', $event);
        abort_unless($enrollment->event_id === $event->id, 404);

        $enrollment->update($request->validated());

        return back()->with('success', 'Inscripción actualizada.');
    }
}
