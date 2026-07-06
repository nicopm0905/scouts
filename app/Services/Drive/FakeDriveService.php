<?php

namespace App\Services\Drive;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Implementación en memoria para tests y desarrollo local sin credenciales.
 * No toca la red. Guarda los contenidos en un array estático para poder
 * hacer aserciones en los tests.
 */
class FakeDriveService implements DriveServiceInterface
{
    /** @var array<string, array{file: DriveFile, contents: string}> */
    public static array $files = [];

    /** @var array<string, array{name: string, parent: ?string}> */
    public static array $folders = [];

    public static function reset(): void
    {
        self::$files = [];
        self::$folders = [];
    }

    public function upload(UploadedFile $file, ?string $folderId = null, ?string $name = null): DriveFile
    {
        return $this->store(
            $file->get(),
            $name ?? $file->getClientOriginalName(),
            $file->getMimeType() ?? 'application/octet-stream',
        );
    }

    public function uploadRaw(string $contents, string $name, string $mimeType, ?string $folderId = null): DriveFile
    {
        return $this->store($contents, $name, $mimeType);
    }

    private function store(string $contents, string $name, string $mimeType): DriveFile
    {
        $id = 'fake-'.Str::random(24);
        $file = new DriveFile(
            id: $id,
            name: $name,
            mimeType: $mimeType,
            size: strlen($contents),
            webViewLink: "https://drive.fake/view/{$id}",
            thumbnailLink: "https://drive.fake/thumb/{$id}",
        );

        self::$files[$id] = ['file' => $file, 'contents' => $contents];

        return $file;
    }

    public function createFolder(string $name, ?string $parentId = null): string
    {
        $id = 'folder-'.Str::random(20);
        self::$folders[$id] = ['name' => $name, 'parent' => $parentId];

        return $id;
    }

    public function download(string $fileId): string
    {
        return self::$files[$fileId]['contents'] ?? throw new \RuntimeException("Fichero {$fileId} no encontrado");
    }

    public function getMetadata(string $fileId): ?DriveFile
    {
        return self::$files[$fileId]['file'] ?? null;
    }

    public function thumbnailUrl(string $fileId): ?string
    {
        return self::$files[$fileId]['file']->thumbnailLink ?? null;
    }

    public function webViewLink(string $fileId): ?string
    {
        return self::$files[$fileId]['file']->webViewLink ?? null;
    }

    public function delete(string $fileId): bool
    {
        if (isset(self::$files[$fileId])) {
            unset(self::$files[$fileId]);

            return true;
        }

        return false;
    }
}
