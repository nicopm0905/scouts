<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Cifra en reposo los valores médicos que existían ANTES de añadir los casts
 * `encrypted` a HealthRecord. Es idempotente: si un valor ya descifra
 * correctamente con el APP_KEY actual, se deja tal cual.
 *
 * Uso: php artisan health:encrypt-existing [--dry-run]
 */
class EncryptExistingHealthDataCommand extends Command
{
    protected $signature = 'health:encrypt-existing {--dry-run : Muestra qué se cifraría sin escribir nada}';

    protected $description = 'Cifra los datos médicos existentes en health_records (idempotente)';

    private const FIELDS = ['allergies', 'intolerances', 'medication', 'observations', 'health_card_number'];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $updatedRows = 0;

        DB::table('health_records')->orderBy('id')->chunkById(100, function ($records) use ($dryRun, &$updatedRows) {
            foreach ($records as $record) {
                $changes = [];

                foreach (self::FIELDS as $field) {
                    $value = $record->{$field};

                    if ($value === null || $value === '') {
                        continue;
                    }

                    if ($this->isAlreadyEncrypted($value)) {
                        continue;
                    }

                    $changes[$field] = Crypt::encryptString($value);
                }

                if ($changes === []) {
                    continue;
                }

                $updatedRows++;

                if ($dryRun) {
                    $this->line("  [dry-run] health_records#{$record->id}: ".implode(', ', array_keys($changes)));

                    continue;
                }

                DB::table('health_records')->where('id', $record->id)->update($changes);
            }
        });

        $this->info($dryRun
            ? "Dry-run: {$updatedRows} fichas tienen valores en claro pendientes de cifrar."
            : "Cifradas {$updatedRows} fichas sanitarias (el resto ya estaba cifrado).");

        return self::SUCCESS;
    }

    /** Un valor está cifrado si Crypt puede descifrarlo con el APP_KEY actual. */
    private function isAlreadyEncrypted(string $value): bool
    {
        try {
            Crypt::decryptString($value);

            return true;
        } catch (DecryptException) {
            return false;
        }
    }
}
