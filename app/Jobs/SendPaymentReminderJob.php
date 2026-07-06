<?php

namespace App\Jobs;

use App\Mail\PaymentReminderMail;
use App\Models\ChargeMember;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

/** Envía el recordatorio de pago de un cobro pendiente y marca reminder_sent_at. */
class SendPaymentReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $chargeMemberId)
    {
    }

    public function handle(): void
    {
        $chargeMember = ChargeMember::with(['charge', 'member'])->find($this->chargeMemberId);

        if (! $chargeMember || $chargeMember->reminder_sent_at) {
            return;
        }

        $email = $chargeMember->member->email;

        if ($email) {
            Mail::to($email)->send(new PaymentReminderMail($chargeMember));
        }

        $chargeMember->update(['reminder_sent_at' => now()]);
    }
}
