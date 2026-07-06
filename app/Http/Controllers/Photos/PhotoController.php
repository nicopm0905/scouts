<?php

namespace App\Http\Controllers\Photos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Photos\StorePhotoRequest;
use App\Models\Album;
use App\Models\Photo;
use App\Services\Drive\DriveServiceInterface;
use Illuminate\Http\RedirectResponse;

class PhotoController extends Controller
{
    public function __construct(private readonly DriveServiceInterface $drive)
    {
    }

    public function store(StorePhotoRequest $request, Album $album): RedirectResponse
    {
        $this->authorize('update', $album);

        $uploaded = $this->drive->upload($request->file('file'), $album->drive_folder_id);

        $album->photos()->create([
            'drive_file_id' => $uploaded->id,
            'caption' => $request->validated('caption'),
            'position' => $album->photos()->count(),
        ]);

        return back()->with('success', 'Foto subida correctamente.');
    }

    public function destroy(Photo $photo): RedirectResponse
    {
        $this->authorize('update', $photo->album);

        $this->drive->delete($photo->drive_file_id);
        $photo->delete();

        return back()->with('success', 'Foto eliminada correctamente.');
    }
}
