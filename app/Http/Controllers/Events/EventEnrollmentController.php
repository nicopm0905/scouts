<?php

namespace App\Http\Controllers\Events;

use App\Http\Controllers\Controller;
use App\Http\Requests\Events\UpdateEnrollmentRequest;
use App\Models\Event;
use App\Models\EventMember;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;

/** Gestión (por el equipo, no por las familias) de las inscripciones de un evento. */
class EventEnrollmentController extends Controller
{
    use AuthorizesRequests;

    public function update(UpdateEnrollmentRequest $request, Event $event, EventMember $enrollment): RedirectResponse
    {
        $this->authorize('update', $event);
        abort_unless($enrollment->event_id === $event->id, 404);

        $enrollment->update($request->validated());

        return back()->with('success', 'Inscripción actualizada.');
    }
}
