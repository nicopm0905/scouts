<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\HistoryEntry;
use App\Services\Drive\DriveServiceInterface;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Página pública (sin auth) con la línea de tiempo de la historia del grupo.
 * Solo muestra entradas publicadas (published = true), ordenadas por año.
 */
class HistoryPublicController extends Controller
{
    public function __construct(private readonly DriveServiceInterface $drive)
    {
    }

    public function show(): Response
    {
        $entries = HistoryEntry::query()
            ->where('published', true)
            ->orderBy('year')
            ->orderBy('position')
            ->get()
            ->map(fn (HistoryEntry $entry) => [
                'id' => $entry->id,
                'year' => $entry->year,
                'title' => $entry->title,
                'body' => $entry->body,
                'photo_url' => $entry->photo_file_id ? $this->drive->thumbnailUrl($entry->photo_file_id) : null,
            ]);

        return Inertia::render('Public/History/Show', [
            'entries' => $entries,
        ]);
    }
}
