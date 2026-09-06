<?php

namespace App\Services\Members;

use App\Enums\FamilyRelationship;
use App\Enums\MemberRole;
use App\Models\Family;
use App\Models\Member;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Spatie\SimpleExcel\SimpleExcelReader;
use Spatie\SimpleExcel\SimpleExcelWriter;

/**
 * Importa miembros desde un CSV o Excel con cabecera:
 * first_name,last_name,phone,email,role,birth_date,joined_at,active,notes[,family_name,family_phone,family_email]
 *
 * - Upsert: si ya existe un miembro con mismo nombre + apellidos + fecha de nacimiento,
 *   se actualiza en vez de crear (reimportar no duplica).
 * - Columnas de familia opcionales: crea o reutiliza la Family por nombre y la vincula.
 * - Compatible con el formato antiguo (sin columnas de familia): las columnas se resuelven
 *   por el nombre de la cabecera y, si no es reconocible, por posición.
 */
class MemberImportService
{
    public const HEADER = ['first_name', 'last_name', 'phone', 'email', 'role', 'birth_date', 'joined_at', 'active', 'notes'];

    public const FAMILY_COLUMNS = ['family_name', 'family_phone', 'family_email'];

    /** @return array{created:int, updated:int, errors: array<int, array{row:int, errors: array<int,string>}>} */
    public function import(UploadedFile $file): array
    {
        $created = 0;
        $updated = 0;
        $errors = [];

        try {
            $reader = SimpleExcelReader::create($file->getRealPath(), $file->getClientOriginalExtension() === 'csv' ? 'csv' : 'xlsx');

            // Si el archivo está vacío
            if ($reader->getRows()->count() === 0) {
                return ['created' => 0, 'updated' => 0, 'errors' => [['row' => 0, 'errors' => ['El fichero está vacío o no se pudo leer.']]]];
            }

            $header = $reader->getHeaders();
            $columns = $this->resolveColumns($header ?? []);

            $rowNumber = 1;

            $reader->getRows()->each(function (array $rowArray) use (&$created, &$updated, &$errors, &$rowNumber, $columns) {
                $rowNumber++;

                // Extraer los valores en el mismo orden que la cabecera para poder usar mapRow
                $row = array_values($rowArray);

                // Check for empty row
                if (count($row) === 1 && $row[0] === null) {
                    return; // continue equivalent
                }

                $record = $this->normalize($this->mapRow($columns, $row));

                $validator = Validator::make($record, [
                    'first_name' => ['required', 'string', 'max:100'],
                    'last_name' => ['required', 'string', 'max:150'],
                    'phone' => ['nullable', 'string', 'max:30'],
                    'email' => ['nullable', 'email', 'max:150'],
                    'role' => ['required', 'in:'.implode(',', MemberRole::values())],
                    'birth_date' => ['nullable', 'date'],
                    'joined_at' => ['nullable', 'date'],
                    'active' => ['boolean'],
                    'notes' => ['nullable', 'string'],
                    'family_name' => ['nullable', 'string', 'max:150'],
                    'family_phone' => ['nullable', 'string', 'max:30'],
                    'family_email' => ['nullable', 'email', 'max:150'],
                ]);

                if ($validator->fails()) {
                    $errors[] = ['row' => $rowNumber, 'errors' => $validator->errors()->all()];

                    return;
                }

                $data = $validator->validated();
                $familyName = $data['family_name'] ?? null;
                $familyPhone = $data['family_phone'] ?? null;
                $familyEmail = $data['family_email'] ?? null;
                unset($data['family_name'], $data['family_phone'], $data['family_email']);

                // Upsert por nombre + apellidos + fecha de nacimiento.
                $member = Member::query()
                    ->where('first_name', $data['first_name'])
                    ->where('last_name', $data['last_name'])
                    ->when(
                        $data['birth_date'],
                        fn ($q, $date) => $q->whereDate('birth_date', Carbon::parse($date)->toDateString()),
                        fn ($q) => $q->whereNull('birth_date'),
                    )
                    ->first();

                if ($member) {
                    $member->update($data);
                    $updated++;
                } else {
                    $member = Member::create($data);
                    $created++;
                }

                if ($familyName !== null) {
                    $this->linkFamily($member, $familyName, $familyPhone, $familyEmail);
                }
            });

        } catch (\Exception $e) {
            return ['created' => 0, 'updated' => 0, 'errors' => [['row' => 0, 'errors' => ['Error procesando el fichero: '.$e->getMessage()]]]];
        }

        return ['created' => $created, 'updated' => $updated, 'errors' => $errors];
    }

    /** Genera el contenido XLSX de la plantilla descargable. */
    public function templateExcel(): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'template').'.xlsx';
        $writer = SimpleExcelWriter::create($tempFile);

        $header = array_merge(self::HEADER, self::FAMILY_COLUMNS);
        $writer->addHeader($header);

        $row = array_combine($header, ['Ana', 'García Pérez', '600111222', 'ana@example.com', 'lobato', '2016-04-01', '2023-09-01', '1', '', 'Familia García', '600999888', 'familia.garcia@example.com']);
        $writer->addRow($row);
        $writer->close();

        $contents = file_get_contents($tempFile);
        unlink($tempFile);

        return $contents;
    }

    /**
     * Resuelve el nombre de cada columna a partir de la cabecera del fichero.
     * Si la cabecera no es reconocible, se asume el orden clásico (compatibilidad).
     *
     * @return array<int, string|null>
     */
    private function resolveColumns(array $header): array
    {
        $known = array_merge(self::HEADER, self::FAMILY_COLUMNS);
        $normalized = array_map(fn ($cell) => strtolower(trim((string) $cell)), $header);

        if (! in_array('first_name', $normalized, true)) {
            return self::HEADER; // cabecera antigua o irreconocible: por posición
        }

        return array_map(fn (string $cell) => in_array($cell, $known, true) ? $cell : null, $normalized);
    }

    /** @param array<int, string|null> $columns */
    private function mapRow(array $columns, array $row): array
    {
        $record = [];

        foreach ($columns as $index => $column) {
            if ($column !== null) {
                $record[$column] = $row[$index] ?? null;
            }
        }

        return $record;
    }

    /** Crea o reutiliza la familia por nombre y la vincula al miembro. */
    private function linkFamily(Member $member, string $name, ?string $phone, ?string $email): void
    {
        $family = Family::firstOrCreate(
            ['name' => $name],
            ['contact_phone' => $phone, 'contact_email' => $email],
        );

        $member->families()->syncWithoutDetaching([
            $family->id => ['relationship' => FamilyRelationship::Tutor->value],
        ]);
    }

    private function normalize(array $record): array
    {
        $record['phone'] = $record['phone'] ?? null;
        $record['email'] = ($record['email'] ?? null) ?: null;
        $record['birth_date'] = ($record['birth_date'] ?? null) ?: null;
        $record['joined_at'] = ($record['joined_at'] ?? null) ?: null;
        $record['notes'] = $record['notes'] ?? null;
        $record['active'] = filter_var($record['active'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $record['family_name'] = trim((string) ($record['family_name'] ?? '')) ?: null;
        $record['family_phone'] = ($record['family_phone'] ?? null) ?: null;
        $record['family_email'] = ($record['family_email'] ?? null) ?: null;

        return $record;
    }
}
