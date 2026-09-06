<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Album;
use App\Models\HistoryEntry;
use App\Services\Drive\DriveServiceInterface;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Portada pública del grupo (sin autenticación): quiénes somos, secciones,
 * qué hacemos, galería, historia y contacto.
 *
 * El contenido editorial vive en config/group.php. Los bloques dinámicos
 * (fotos e hitos) se leen de la BD y degradan a vacío si algo falla, para que
 * la portada nunca deje de renderizarse.
 */
class HomeController extends Controller
{
    public function __construct(private readonly DriveServiceInterface $drive) {}

    public function __invoke(): Response
    {
        return Inertia::render('Public/Home', [
            'group' => $this->groupContent(),
            'gallery' => $this->galleryPreview(),
            'milestones' => $this->milestones(),
        ]);
    }

    /**
     * Contenido editorial + rutas de foto ya resueltas (null si el fichero no existe).
     *
     * @return array<string, mixed>
     */
    private function groupContent(): array
    {
        $group = config('group');

        $group['photos'] = collect($group['photos'] ?? [])
            ->map(fn (?string $file) => $this->photoUrl($file))
            ->all();

        $group['sections'] = collect($group['sections'] ?? [])
            ->map(function (array $section) {
                $section['photo'] = $this->photoUrl($section['photo'] ?? null);

                return $section;
            })
            ->all();

        return $group;
    }

    /**
     * URL pública de una foto de landing, solo si el fichero existe **y** está en un
     * formato que los navegadores saben mostrar.
     *
     * Motivo: las fotos del móvil suelen ser HEIC aunque se renombren a .jpg, y el
     * navegador no las decodifica (la web se veía rota). Si el formato no es válido,
     * devolvemos null y la página dibuja su fondo ilustrado de respaldo.
     */
    private function photoUrl(?string $file): ?string
    {
        if (! $file) {
            return null;
        }

        $relative = 'images/landing/'.ltrim($file, '/');
        $absolute = public_path($relative);

        if (! is_file($absolute)) {
            return null;
        }

        if (! $this->isWebImage($absolute)) {
            Log::warning('Landing: foto ignorada por formato no compatible con navegadores (¿HEIC del móvil renombrado?).', [
                'fichero' => $relative,
            ]);

            return null;
        }

        return '/'.$relative;
    }

    /** Comprueba la firma del fichero: JPEG, PNG, GIF, WebP o AVIF. */
    private function isWebImage(string $absolute): bool
    {
        $handle = @fopen($absolute, 'rb');

        if ($handle === false) {
            return false;
        }

        $header = fread($handle, 16) ?: '';
        fclose($handle);

        if (strlen($header) < 12) {
            return false;
        }

        return str_starts_with($header, "\xFF\xD8\xFF")                       // JPEG
            || str_starts_with($header, "\x89PNG\r\n\x1a\n")                  // PNG
            || str_starts_with($header, 'GIF8')                               // GIF
            || (str_starts_with($header, 'RIFF') && substr($header, 8, 4) === 'WEBP')
            || substr($header, 4, 8) === 'ftypavif';                          // AVIF
    }

    /**
     * Hasta 8 fotos de álbumes publicables, para el mosaico de la portada.
     *
     * @return array<int, array<string, mixed>>
     */
    private function galleryPreview(): array
    {
        try {
            return Album::query()
                ->where('visibility', 'publishable')
                ->with('photos')
                ->latest()
                ->limit(4)
                ->get()
                ->flatMap(fn (Album $album) => $album->photos->map(fn ($photo) => [
                    'id' => $photo->id,
                    'album' => $album->title,
                    'caption' => $photo->caption,
                    'url' => $this->drive->thumbnailUrl($photo->drive_file_id),
                ]))
                ->filter(fn (array $photo) => (bool) $photo['url'])
                ->take(8)
                ->values()
                ->all();
        } catch (\Throwable $e) {
            Log::warning('Landing: no se pudo cargar la galería pública.', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * Últimos hitos publicados de la historia del grupo.
     *
     * @return array<int, array<string, mixed>>
     */
    private function milestones(): array
    {
        try {
            return HistoryEntry::query()
                ->where('published', true)
                ->orderByDesc('year')
                ->orderBy('position')
                ->limit(3)
                ->get()
                ->map(fn (HistoryEntry $entry) => [
                    'id' => $entry->id,
                    'year' => $entry->year,
                    'title' => $entry->title,
                ])
                ->all();
        } catch (\Throwable $e) {
            Log::warning('Landing: no se pudo cargar la historia del grupo.', ['error' => $e->getMessage()]);

            return [];
        }
    }
}
