<?php

namespace App\Services\Drive;

use Illuminate\Http\UploadedFile;

/**
 * Contrato de almacenamiento de ficheros. Los módulos NUNCA guardan ficheros en el
 * servidor: usan esta interfaz e persisten solo el file_id / folder_id devuelto.
 *
 * Implementaciones: GoogleDriveService (producción), FakeDriveService (tests/local).
 */
interface DriveServiceInterface
{
    /** Sube un fichero a una carpeta (o a la raíz configurada) y devuelve sus metadatos. */
    public function upload(UploadedFile $file, ?string $folderId = null, ?string $name = null): DriveFile;

    /** Sube contenido en crudo (p. ej. un PDF generado en memoria). */
    public function uploadRaw(string $contents, string $name, string $mimeType, ?string $folderId = null): DriveFile;

    /** Crea una carpeta (opcionalmente dentro de otra) y devuelve su id. */
    public function createFolder(string $name, ?string $parentId = null): string;

    /** Descarga el contenido en crudo de un fichero. */
    public function download(string $fileId): string;

    /** Metadatos de un fichero, o null si no existe. */
    public function getMetadata(string $fileId): ?DriveFile;

    /** URL de miniatura (para galerías de fotos). */
    public function thumbnailUrl(string $fileId): ?string;

    /** Enlace de visualización web. */
    public function webViewLink(string $fileId): ?string;

    /** Elimina un fichero. Devuelve true si se eliminó. */
    public function delete(string $fileId): bool;
}
