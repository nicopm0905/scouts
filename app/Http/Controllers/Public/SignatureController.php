<?php

namespace App\Http\Controllers\Public;

use App\Enums\SignatureStatus;
use App\Http\Controllers\Controller;
use App\Models\Consent;
use App\Models\Document;
use App\Models\Signature;
use App\Services\Drive\DriveServiceInterface;
use App\Services\Signature\SignatureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Página pública (sin autenticación) para que la familia revise y firme
 * digitalmente un documento. Acceso por public_token individual.
 */
class SignatureController extends Controller
{
    public function show(string $token, DriveServiceInterface $drive): Response
    {
        $signature = Signature::where('public_token', $token)->with(['signable', 'member'])->firstOrFail();

        if ($signature->status === SignatureStatus::Pending || $signature->status === SignatureStatus::Sent) {
            if ($signature->expires_at && $signature->expires_at->isPast()) {
                $signature->update(['status' => SignatureStatus::Expired]);
            }
        }

        $documentUrl = null;
        if ($signature->signable instanceof Document && $signature->signable->drive_file_id) {
            $documentUrl = $drive->webViewLink($signature->signable->drive_file_id);
        } elseif ($signature->signable instanceof Document) {
            $documentUrl = $signature->signable->external_url;
        }

        return Inertia::render('Public/Signature', [
            'token' => $token,
            'title' => $signature->title(),
            'member_name' => $signature->member->full_name,
            'document_url' => $documentUrl,
            'legal_text' => $signature->signable instanceof Consent ? $signature->signable->type->legalText() : null,
            'status' => $signature->status->value,
            'signed_at' => $signature->signed_at?->toIso8601String(),
        ]);
    }

    public function sign(Request $request, string $token, SignatureService $service): RedirectResponse
    {
        $signature = Signature::where('public_token', $token)->with(['signable', 'member'])->firstOrFail();

        abort_if($signature->status === SignatureStatus::Signed, 409);
        abort_if($signature->expires_at?->isPast(), 410);

        $data = $request->validate([
            'signer_name' => ['required', 'string', 'max:255'],
            'signature_image' => ['required', 'string'],
            'accepted' => ['required', 'accepted'],
        ]);

        $service->sign($signature, $data['signer_name'], $data['signature_image'], $request);

        return back()->with('success', 'Documento firmado correctamente.');
    }
}
