<?php

namespace App\Services\Members;

use App\Enums\MemberRole;
use App\Models\Member;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;

/**
 * Importa miembros desde un CSV con cabecera:
 * first_name,last_name,phone,email,role,birth_date,joined_at,active,notes
 *
 * Usa fgetcsv nativo (sin paquetes nuevos), tal como exige CONVENTIONS.md.
 */
class MemberImportService
{
    public const HEADER = ['first_name', 'last_name', 'phone', 'email', 'role', 'birth_date', 'joined_at', 'active', 'notes'];

    /** @return array{created:int, errors: array<int, array{row:int, errors: array<int,string>}>} */
    public function import(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');

        $created = 0;
        $errors = [];

        if ($handle === false) {
            return ['created' => 0, 'errors' => [['row' => 0, 'errors' => ['No se pudo leer el fichero.']]]];
        }

        $header = fgetcsv($handle, escape: '\\');
        $rowNumber = 1;

        while (($row = fgetcsv($handle, escape: '\\')) !== false) {
            $rowNumber++;

            if (count($row) === 1 && $row[0] === null) {
                continue; // línea vacía
            }

            $record = array_combine(
                array_slice(self::HEADER, 0, count($row)),
                $row
            );

            $record = $this->normalize($record);

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
            ]);

            if ($validator->fails()) {
                $errors[] = ['row' => $rowNumber, 'errors' => $validator->errors()->all()];

                continue;
            }

            Member::create($validator->validated());
            $created++;
        }

        fclose($handle);

        return ['created' => $created, 'errors' => $errors];
    }

    /** Genera el contenido CSV de la plantilla descargable. */
    public function templateCsv(): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, self::HEADER);
        fputcsv($handle, ['Ana', 'García Pérez', '600111222', 'ana@example.com', 'lobato', '2016-04-01', '2023-09-01', '1', '']);
        rewind($handle);
        $contents = stream_get_contents($handle);
        fclose($handle);

        return $contents;
    }

    private function normalize(array $record): array
    {
        $record['phone'] = $record['phone'] ?? null;
        $record['email'] = $record['email'] ?: null;
        $record['birth_date'] = $record['birth_date'] ?: null;
        $record['joined_at'] = $record['joined_at'] ?: null;
        $record['notes'] = $record['notes'] ?? null;
        $record['active'] = filter_var($record['active'] ?? true, FILTER_VALIDATE_BOOLEAN);

        return $record;
    }
}
