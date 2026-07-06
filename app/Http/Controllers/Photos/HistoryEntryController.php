<?php

namespace App\Http\Controllers\Photos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Photos\StoreHistoryEntryRequest;
use App\Http\Requests\Photos\UpdateHistoryEntryRequest;
use App\Models\HistoryEntry;
use App\Services\Drive\DriveServiceInterface;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class HistoryEntryController extends Controller
{
    public function __construct(private readonly DriveServiceInterface $drive)
    {
    }

    public function index(): Response
    {
        $this->authorize('viewAny', HistoryEntry::class);

        $entries = HistoryEntry::query()
            ->orderBy('year')
            ->orderBy('position')
            ->get()
            ->map(fn (HistoryEntry $entry) => $this->present($entry));

        return Inertia::render('History/Index', [
            'entries' => $entries,
        ]);
    }

    public function store(StoreHistoryEntryRequest $request): RedirectResponse
    {
        $this->authorize('create', HistoryEntry::class);

        HistoryEntry::create($this->dataWithPhoto($request));

        return back()->with('success', 'Entrada creada correctamente.');
    }

    public function update(UpdateHistoryEntryRequest $request, HistoryEntry $history_entry): RedirectResponse
    {
        $this->authorize('update', $history_entry);

        $history_entry->update($this->dataWithPhoto($request));

        return back()->with('success', 'Entrada actualizada correctamente.');
    }

    public function destroy(HistoryEntry $history_entry): RedirectResponse
    {
        $this->authorize('delete', $history_entry);

        $history_entry->delete();

        return back()->with('success', 'Entrada eliminada correctamente.');
    }

    /**
     * @return array<string, mixed>
     */
    private function dataWithPhoto(StoreHistoryEntryRequest|UpdateHistoryEntryRequest $request): array
    {
        $data = $request->safe()->except('photo');

        if ($request->hasFile('photo')) {
            $uploaded = $this->drive->upload($request->file('photo'));
            $data['photo_file_id'] = $uploaded->id;
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function present(HistoryEntry $entry): array
    {
        return [
            'id' => $entry->id,
            'year' => $entry->year,
            'title' => $entry->title,
            'body' => $entry->body,
            'published' => $entry->published,
            'position' => $entry->position,
            'photo_file_id' => $entry->photo_file_id,
            'photo_thumbnail_url' => $entry->photo_file_id ? $this->drive->thumbnailUrl($entry->photo_file_id) : null,
        ];
    }
}
