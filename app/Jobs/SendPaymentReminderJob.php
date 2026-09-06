<?php

namespace App\Jobs;

use App\Mail\PaymentReminderMail;
use App\Models\ChargeMember;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Envía el recordatorio de pago de un cobro pendiente y marca reminder_sent_at.
 *
 * El destinatario se resuelve con Member::contactEmail(): primero el email de
 * contacto de la familia (los niños no suelen tener email propio) y, si no hay,
 * el email del propio miembro. Si no existe ninguno, NO se marca reminder_sent_at
 * (así el cobro sigue apareciendo como "sin avisar" y volverá a detectarse).
 */
class SendPaymentReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $chargeMemberId) {}

    public function handle(): void
    {
        $chargeMember = ChargeMember::with(['charge', 'member.families'])->find($this->chargeMemberId);

        if (! $chargeMember || $chargeMember->reminder_sent_at) {
            return;
        }

        $email = $chargeMember->member?->contactEmail();

        if (! $email) {
            Log::warning('Recordatorio de pago NO enviado: el miembro no tiene email de familia ni propio', [
                'charge_member_id' => $chargeMember->id,
                'member_id' => $chargeMember->member_id,
                'member' => $chargeMember->member?->full_name,
                'charge' => $chargeMember->charge?->title,
            ]);

            return; // Sin destinatario no hay aviso: no marcamos reminder_sent_at.
        }

        Mail::to($email)->send(new PaymentReminderMail($chargeMember));

        $chargeMember->update(['reminder_sent_at' => now()]);
    }
}
