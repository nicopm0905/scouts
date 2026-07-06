<?php

namespace App\Http\Controllers\Photos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Photos\StoreAlbumRequest;
use App\Http\Requests\Photos\UpdateAlbumRequest;
use App\Models\Album;
use App\Models\Event;
use App\Services\Drive\DriveServiceInterface;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AlbumController extends Controller
{
    public function __construct(private readonly DriveServiceInterface $drive)
    {
    }

    public function index(): Response
    {
        $this->authorize('viewAny', Album::class);

        $albums = Album::query()
            ->with('event')
            ->withCount('photos')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Album $album) => $this->presentAlbum($album));

        return Inertia::render('Albums/Index', [
            'albums' => $albums,
            'events' => Event::query()->orderByDesc('start_at')->get(['id', 'title']),
        ]);
    }

    public function store(StoreAlbumRequest $request): RedirectResponse
    {
        $this->authorize('create', Album::class);

        $folderId = $this->drive->createFolder($request->validated('title'));

        Album::create([
            ...$request->validated(),
            'drive_folder_id' => $folderId,
        ]);

        return back()->with('success', 'Álbum creado correctamente.');
    }

    public function show(Album $album): Response
    {
        $this->authorize('view', $album);

        $album->load('event');

        $photos = $album->photos->map(fn ($photo) => [
            'id' => $photo->id,
            'caption' => $photo->caption,
            'position' => $photo->position,
            'thumbnail_url' => $this->drive->thumbnailUrl($photo->drive_file_id),
        ]);

        return Inertia::render('Albums/Show', [
            'album' => $this->presentAlbum($album),
            'photos' => $photos,
        ]);
    }

    public function update(UpdateAlbumRequest $request, Album $album): RedirectResponse
    {
        $this->authorize('update', $album);

        $album->update($request->validated());

        return back()->with('success', 'Álbum actualizado correctamente.');
    }

    public function destroy(Album $album): RedirectResponse
    {
        $this->authorize('delete', $album);

        foreach ($album->photos as $photo) {
            $this->drive->delete($photo->drive_file_id);
        }

        $album->delete();

        return back()->with('success', 'Álbum eliminado correctamente.');
    }

    /** Galería pública: solo álbumes con consentimiento de imagen (visibility = publishable). */
    public function publicIndex(): Response
    {
        $albums = Album::query()
            ->where('visibility', 'publishable')
            ->with('photos')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Album $album) => [
                'id' => $album->id,
                'title' => $album->title,
                'description' => $album->description,
                'photos' => $album->photos->map(fn ($photo) => [
                    'id' => $photo->id,
                    'caption' => $photo->caption,
                    'thumbnail_url' => $this->drive->thumbnailUrl($photo->drive_file_id),
                ]),
            ]);

        return Inertia::render('Albums/Public', [
            'albums' => $albums,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function presentAlbum(Album $album): array
    {
        return [
            'id' => $album->id,
            'title' => $album->title,
            'description' => $album->description,
            'visibility' => $album->visibility,
            'event' => $album->event ? ['id' => $album->event->id, 'title' => $album->event->title] : null,
            'photos_count' => $album->photos_count ?? $album->photos()->count(),
        ];
    }
}
