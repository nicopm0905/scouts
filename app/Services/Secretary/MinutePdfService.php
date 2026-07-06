<?php

namespace App\Services\Secretary;

use App\Models\Minute;
use App\Services\Drive\DriveFile;
use App\Services\Drive\DriveServiceInterface;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

/**
 * Genera el PDF de un acta (dompdf) y lo sube a Drive.
 * NUNCA se guarda el fichero en el servidor: solo se genera en memoria y se sube.
 */
class MinutePdfService
{
    public function __construct(private readonly DriveServiceInterface $drive)
    {
    }

    /** Genera el PDF del acta, lo sube a Drive y devuelve el fichero subido. */
    public function generateAndUpload(Minute $minute): DriveFile
    {
        $minute->loadMissing(['items', 'attendees', 'creator']);

        $pdf = Pdf::loadView('pdf.minute', ['minute' => $minute]);
        $contents = $pdf->output();

        $name = Str::slug($minute->title ?: 'acta').'-'.$minute->held_on->format('Y-m-d').'.pdf';

        return $this->drive->uploadRaw($contents, $name, 'application/pdf');
    }
}
