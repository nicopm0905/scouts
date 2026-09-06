<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/** Calendario del portal: eventos de las ramas de los scouts a cargo de la cuenta. */
class CalendarController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $branches = $user->children()
            ->pluck('role')
            ->map(fn ($r) => $r->value)
            ->unique()
            ->values()
            ->all();

        $events = Event::query()
            ->where('start_at', '>=', now()->subMonth())
            ->where(function ($q) use ($branches) {
                foreach ($branches ?: ['__none__'] as $b) {
                    $q->orWhereJsonContains('branches', $b);
                }
            })
            ->orderBy('start_at')
            ->get(['id', 'title', 'type', 'start_at', 'end_at', 'location', 'description'])
            ->map(fn (Event $e) => [
                'id' => $e->id,
                'title' => $e->title,
                'type' => $e->type->label(),
                'type_key' => $e->type->value,
                'start_at' => $e->start_at?->toIso8601String(),
                'end_at' => $e->end_at?->toIso8601String(),
                'location' => $e->location,
                'description' => $e->description,
            ]);

        return Inertia::render('Portal/Calendar', [
            'events' => $events,
            'icalUrl' => route('public.ical.show', $user->ensureIcalToken()),
        ]);
    }

    public function regenerateToken(Request $request): RedirectResponse
    {
        $request->user()->forceFill(['ical_token' => Str::random(48)])->save();

        return back()->with('success', 'Enlace del calendario regenerado. El anterior ha dejado de funcionar.');
    }
}
