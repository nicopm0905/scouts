<?php

namespace App\Http\Controllers\Events;

use App\Http\Controllers\Controller;
use App\Http\Requests\Events\StoreChecklistItemRequest;
use App\Http\Requests\Events\UpdateChecklistItemRequest;
use App\Models\Event;
use App\Models\EventChecklistItem;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;

class EventChecklistController extends Controller
{
    use AuthorizesRequests;

    public function store(StoreChecklistItemRequest $request, Event $event): RedirectResponse
    {
        $this->authorize('update', $event);

        $position = $event->checklistItems()->max('position') + 1;

        $event->checklistItems()->create([
            'label' => $request->validated('label'),
            'position' => $position,
        ]);

        return back()->with('success', 'Punto de la checklist añadido.');
    }

    public function update(UpdateChecklistItemRequest $request, Event $event, EventChecklistItem $item): RedirectResponse
    {
        $this->authorize('update', $event);
        abort_unless($item->event_id === $event->id, 404);

        $item->update($request->validated());

        return back()->with('success', 'Checklist actualizada.');
    }

    public function destroy(Event $event, EventChecklistItem $item): RedirectResponse
    {
        $this->authorize('update', $event);
        abort_unless($item->event_id === $event->id, 404);

        $item->delete();

        return back()->with('success', 'Punto de la checklist eliminado.');
    }
}
