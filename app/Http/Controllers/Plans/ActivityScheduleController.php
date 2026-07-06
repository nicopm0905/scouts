<?php

namespace App\Http\Controllers\Plans;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/** Programa/quita una actividad de la biblioteca en un evento del calendario (pivote activity_event). */
class ActivityScheduleController extends Controller
{
    public function store(Activity $activity, Event $event): RedirectResponse
    {
        Gate::authorize('update', $activity);
        Gate::authorize('update', $event);

        $activity->events()->syncWithoutDetaching([$event->id]);

        return back()->with('success', 'Actividad programada en el evento.');
    }

    public function destroy(Activity $activity, Event $event): RedirectResponse
    {
        Gate::authorize('update', $activity);
        Gate::authorize('update', $event);

        $activity->events()->detach($event->id);

        return back()->with('success', 'Actividad retirada del evento.');
    }
}
