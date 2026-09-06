<?php

namespace App\Jobs;

use App\Enums\SignatureStatus;
use App\Mail\SignatureRequestMail;
use App\Models\Signature;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Envía el email de solicitud de firma y marca sent_at. El destinatario se resuelve
 * con Member::contactEmail() (email de la familia, o del propio miembro si no hay).
 */
class SendSignatureRequestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $signatureId) {}

    public function handle(): void
    {
        $signature = Signature::with(['signable', 'member.families'])->find($this->signatureId);

        if (! $signature || $signature->status === SignatureStatus::Signed) {
            return;
        }

        $email = $signature->member?->contactEmail();

        if (! $email) {
            Log::warning('Solicitud de firma NO enviada: el miembro no tiene email de familia ni propio', [
                'signature_id' => $signature->id,
                'member_id' => $signature->member_id,
                'member' => $signature->member?->full_name,
            ]);

            return;
        }

        Mail::to($email)->send(new SignatureRequestMail($signature));

        $signature->update(['status' => SignatureStatus::Sent, 'sent_at' => now()]);
    }
}
