<?php

namespace App\Services\Drive;

use Firebase\JWT\JWT;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Implementación con la API REST de Google Drive v3 usando una cuenta de servicio
 * (service account). No usa el SDK pesado google/apiclient: firma un JWT con
 * firebase/php-jwt y llama a los endpoints REST con el cliente HTTP de Laravel.
 *
 * Configuración (config/services.php -> drive):
 *  - service_account: array del JSON de credenciales (client_email, private_key, ...)
 *  - root_folder_id: carpeta raíz compartida con la cuenta de servicio.
 */
class GoogleDriveService implements DriveServiceInterface
{
    private const TOKEN_URI = 'https://oauth2.googleapis.com/token';
    private const SCOPE = 'https://www.googleapis.com/auth/drive';
    private const API = 'https://www.googleapis.com/drive/v3';
    private const UPLOAD_API = 'https://www.googleapis.com/upload/drive/v3';

    /** @param array<string, mixed> $credentials */
    public function __construct(
        private readonly array $credentials,
        private readonly ?string $rootFolderId = null,
    ) {
        if (empty($credentials['client_email']) || empty($credentials['private_key'])) {
            throw new RuntimeException('Credenciales de Google Drive incompletas (client_email/private_key).');
        }
    }

    public function upload(UploadedFile $file, ?string $folderId = null, ?string $name = null): DriveFile
    {
        return $this->uploadRaw(
            $file->get(),
            $name ?? $file->getClientOriginalName(),
            $file->getMimeType() ?? 'application/octet-stream',
            $folderId,
        );
    }

    public function uploadRaw(string $contents, string $name, string $mimeType, ?string $folderId = null): DriveFile
    {
        $metadata = ['name' => $name];
        $parent = $folderId ?? $this->rootFolderId;
        if ($parent) {
            $metadata['parents'] = [$parent];
        }

        $boundary = 'scouts'.uniqid();
        $body = "--{$boundary}\r\n"
            ."Content-Type: application/json; charset=UTF-8\r\n\r\n"
            .json_encode($metadata)."\r\n"
            ."--{$boundary}\r\n"
            ."Content-Type: {$mimeType}\r\n\r\n"
            .$contents."\r\n"
            ."--{$boundary}--";

        $response = $this->client()
            ->withBody($body, "multipart/related; boundary={$boundary}")
            ->post(self::UPLOAD_API.'/files?uploadType=multipart&supportsAllDrives=true&fields='.$this->fields());

        $response->throw();

        return $this->toDriveFile($response->json());
    }

    public function createFolder(string $name, ?string $parentId = null): string
    {
        $metadata = [
            'name' => $name,
            'mimeType' => 'application/vnd.google-apps.folder',
        ];
        $parent = $parentId ?? $this->rootFolderId;
        if ($parent) {
            $metadata['parents'] = [$parent];
        }

        $response = $this->client()
            ->post(self::API.'/files?supportsAllDrives=true&fields=id', $metadata)
            ->throw();

        return $response->json('id');
    }

    public function download(string $fileId): string
    {
        return $this->client()
            ->get(self::API."/files/{$fileId}?alt=media&supportsAllDrives=true")
            ->throw()
            ->body();
    }

    public function getMetadata(string $fileId): ?DriveFile
    {
        $response = $this->client()
            ->get(self::API."/files/{$fileId}?supportsAllDrives=true&fields=".$this->fields());

        if ($response->status() === 404) {
            return null;
        }

        $response->throw();

        return $this->toDriveFile($response->json());
    }

    public function thumbnailUrl(string $fileId): ?string
    {
        return $this->getMetadata($fileId)?->thumbnailLink;
    }

    public function webViewLink(string $fileId): ?string
    {
        return $this->getMetadata($fileId)?->webViewLink;
    }

    public function delete(string $fileId): bool
    {
        $response = $this->client()->delete(self::API."/files/{$fileId}?supportsAllDrives=true");

        return $response->successful() || $response->status() === 404;
    }

    // --- interno ---

    private function fields(): string
    {
        return 'id,name,mimeType,size,webViewLink,thumbnailLink';
    }

    private function toDriveFile(array $data): DriveFile
    {
        return new DriveFile(
            id: $data['id'],
            name: $data['name'] ?? '',
            mimeType: $data['mimeType'] ?? null,
            size: isset($data['size']) ? (int) $data['size'] : null,
            webViewLink: $data['webViewLink'] ?? null,
            thumbnailLink: $data['thumbnailLink'] ?? null,
        );
    }

    private function client(): PendingRequest
    {
        return Http::withToken($this->accessToken())->acceptJson();
    }

    /** Obtiene (y cachea) un access token OAuth2 mediante el flujo JWT de service account. */
    private function accessToken(): string
    {
        $cacheKey = 'google_drive_token:'.md5($this->credentials['client_email']);

        return Cache::remember($cacheKey, 3300, function () {
            $now = time();
            $jwt = JWT::encode([
                'iss' => $this->credentials['client_email'],
                'scope' => self::SCOPE,
                'aud' => self::TOKEN_URI,
                'iat' => $now,
                'exp' => $now + 3600,
            ], $this->credentials['private_key'], 'RS256');

            $response = Http::asForm()->post(self::TOKEN_URI, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ])->throw();

            return $response->json('access_token');
        });
    }
}
