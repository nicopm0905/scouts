<?php

namespace App\Services\Signature;

use App\Enums\SignatureStatus;
use App\Models\Consent;
use App\Models\Document;
use App\Models\Signature;
use App\Services\Drive\DriveServiceInterface;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Genera el PDF firmado con la constancia de firma (nombre, firma dibujada, fecha,
 * IP, hash del documento) y lo archiva en Drive. Sincroniza Consent si aplica.
 */
class SignatureService
{
    public function __construct(private DriveServiceInterface $drive) {}

    public function sign(Signature $signature, string $signerName, string $signatureImage, Request $request): Signature
    {
        $sourceHash = $this->hashOfSourceDocument($signature);

        $pdf = Pdf::loadView('pdf.signature-certificate', [
            'signature' => $signature,
            'title' => $signature->title(),
            'member' => $signature->member,
            'signer_name' => $signerName,
            'signature_image' => $signatureImage,
            'signed_at' => now(),
            'ip' => $request->ip(),
            'document_hash' => $sourceHash,
        ]);

        $fileName = 'firma-'.Str::slug($signature->title()).'-'.Str::slug($signature->member->full_name).'.pdf';
        $driveFile = $this->drive->uploadRaw($pdf->output(), $fileName, 'application/pdf');

        $signature->update([
            'status' => SignatureStatus::Signed,
            'signed_at' => now(),
            'signer_name' => $signerName,
            'signature_image' => $signatureImage,
            'signer_ip' => $request->ip(),
            'signer_user_agent' => $request->userAgent(),
            'source_document_hash' => $sourceHash,
            'signed_drive_file_id' => $driveFile->id,
        ]);

        if ($signature->signable instanceof Consent) {
            $signature->signable->update([
                'granted' => true,
                'signed_at' => now()->toDateString(),
                'drive_file_id' => $driveFile->id,
            ]);
        }

        activity()
            ->performedOn($signature->signable)
            ->withProperties(['signer_name' => $signerName, 'ip' => $request->ip()])
            ->log('Documento firmado digitalmente por la familia');

        return $signature;
    }

    /** Hash del contenido mostrado a la familia antes de firmar, para trazabilidad. */
    private function hashOfSourceDocument(Signature $signature): string
    {
        if ($signature->signable instanceof Document && $signature->signable->drive_file_id) {
            return hash('sha256', $this->drive->download($signature->signable->drive_file_id));
        }

        if ($signature->signable instanceof Consent) {
            return hash('sha256', $signature->signable->type->legalText());
        }

        return hash('sha256', $signature->title());
    }
}
