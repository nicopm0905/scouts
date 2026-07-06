<?php

namespace App\Services\Drive;

/** DTO ligero con los metadatos de un fichero en el proveedor de almacenamiento. */
class DriveFile
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $mimeType = null,
        public readonly ?int $size = null,
        public readonly ?string $webViewLink = null,
        public readonly ?string $thumbnailLink = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'mime_type' => $this->mimeType,
            'size' => $this->size,
            'web_view_link' => $this->webViewLink,
            'thumbnail_link' => $this->thumbnailLink,
        ];
    }
}
