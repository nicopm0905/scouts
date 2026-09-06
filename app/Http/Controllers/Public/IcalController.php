<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\User;
use App\Services\Events\IcalGenerator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** Feed iCalendar de solo lectura del calendario de eventos, vía token personal. */
class IcalController extends Controller
{
    public function show(string $token, Request $request, IcalGenerator $generator): Response
    {
        $user = User::where('ical_token', $token)->firstOrFail();

        $query = Event::query()->orderBy('start_at');

        if ($request->filled('branch')) {
            $query->whereJsonContains('branches', $request->string('branch')->toString());
        }

        $events = $query->get();

        // El feed solo incluye los eventos visibles para el dueño del token:
        // roles globales o usuarios sin ramas asignadas (branches null) ven todo;
        // un responsable ve los eventos de sus ramas y los de grupo (sin ramas).
        if (! $user->canSeeAllBranches() && ! is_null($user->branches)) {
            $events = $events
                ->filter(fn (Event $event) => empty($event->branches)
                    || array_intersect($event->branches, $user->branches))
                ->values();
        }

        $ics = $generator->generate($events);

        return response($ics, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'inline; filename="calendario-msc-andalucia.ics"',
        ]);
    }
}
