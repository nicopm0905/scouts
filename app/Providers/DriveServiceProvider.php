<?php

namespace App\Providers;

use App\Services\Drive\DriveServiceInterface;
use App\Services\Drive\FakeDriveService;
use App\Services\Drive\GoogleDriveService;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class DriveServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DriveServiceInterface::class, function () {
            $config = config('services.drive');

            return match ($config['driver'] ?? 'fake') {
                'google' => new GoogleDriveService(
                    $this->loadCredentials($config['service_account_json'] ?? null),
                    $config['root_folder_id'] ?? null,
                ),
                default => new FakeDriveService,
            };
        });
    }

    /**
     * Acepta una ruta a un fichero JSON o el propio JSON en crudo.
     *
     * @return array<string, mixed>
     */
    private function loadCredentials(?string $source): array
    {
        if (empty($source)) {
            throw new RuntimeException('GOOGLE_DRIVE_SERVICE_ACCOUNT_JSON no está configurado.');
        }

        $actualPath = base_path($source);
        if (is_file($actualPath)) {
            $json = file_get_contents($actualPath);
        } elseif (is_file($source)) {
            $json = file_get_contents($source);
        } else {
            $json = $source;
        }

        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('GOOGLE_DRIVE_SERVICE_ACCOUNT_JSON no es un JSON válido.');
        }

        return $decoded;
    }
}
