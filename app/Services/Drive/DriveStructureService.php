<?php

namespace App\Services\Drive;

use Illuminate\Support\Facades\Cache;

class DriveStructureService
{
    public function __construct(private DriveServiceInterface $drive) {}

    /**
     * Devuelve el string del curso actual (Ronda Solar), ej: "26/27"
     * Se considera que el curso empieza en septiembre (mes 9).
     */
    public function getCurrentCourseString(): string
    {
        $now = now();
        $year = $now->year;

        if ($now->month >= 9) {
            $start = $year;
            $end = $year + 1;
        } else {
            $start = $year - 1;
            $end = $year;
        }

        return substr((string) $start, -2).'/'.substr((string) $end, -2);
    }

    /**
     * Obtiene (y crea si no existe) la carpeta principal "SECRETARÍA XX/YY"
     */
    public function getOrCreateSecretaryFolder(): string
    {
        $courseString = $this->getCurrentCourseString();
        $folderName = "SECRETARÍA {$courseString}";

        $rootId = config('services.drive.secretary_root_folder_id');
        if (! $rootId) {
            throw new \RuntimeException('GOOGLE_DRIVE_SECRETARY_ROOT_FOLDER_ID no configurado en .env.');
        }

        $cacheKey = "drive_secretary_folder_{$courseString}_v2";

        return Cache::rememberForever($cacheKey, function () use ($folderName, $rootId) {
            $existingId = $this->drive->searchFolder($folderName, $rootId);

            if ($existingId) {
                return $existingId;
            }

            // Crear la carpeta raíz del curso
            $newId = $this->drive->createFolder($folderName, $rootId);

            // Crear estructura interna básica automáticamente
            $this->createSubFolders($newId);

            return $newId;
        });
    }

    private function createSubFolders(string $parentId): void
    {
        // 01 - EDUCANDOS
        $educandosId = $this->drive->createFolder('01 - EDUCANDOS', $parentId);
        $this->drive->createFolder('01 - CASTORES', $educandosId, '#ff9800'); // Naranja
        $this->drive->createFolder('02 - LOBATOS', $educandosId, '#f4b400'); // Amarillo
        $this->drive->createFolder('03 - RANGERS', $educandosId, '#4285f4'); // Azul
        $this->drive->createFolder('04 - PIONEROS', $educandosId, '#db4437'); // Rojo

        // 02 y 03
        $this->drive->createFolder('02 - RUTAS ACCIÓN SERVICIO', $parentId);
        $this->drive->createFolder('03 - RESPONSABLES', $parentId);

        // 04 - HISTÓRICO
        $historicoId = $this->drive->createFolder('04 - HISTÓRICO', $parentId);
        $this->drive->createFolder('01 - CASTORES', $historicoId);
        $this->drive->createFolder('02 - LOBATOS', $historicoId);
        $this->drive->createFolder('03 - RANGERS', $historicoId);
        $this->drive->createFolder('04 - PIONEROS', $historicoId);
        $this->drive->createFolder('05 - RUTAS', $historicoId);
        $this->drive->createFolder('06 - RESPONSABLES', $historicoId);
    }
}
