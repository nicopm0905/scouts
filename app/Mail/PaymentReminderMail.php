<?php

namespace App\Mail;

use App\Models\ChargeMember;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Recordatorio de pago de un cobro pendiente, X días antes o el día del vencimiento. */
class PaymentReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ChargeMember $chargeMember) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Recordatorio de pago: '.$this->chargeMember->charge->title,
        );
    }

    public function content(): Content
    {
        $charge = $this->chargeMember->charge;
        $member = $this->chargeMember->member;
        $dueDate = $charge->due_date?->format('d/m/Y') ?? 'sin fecha límite';
        $amount = number_format((float) $this->chargeMember->amount, 2, ',', '.');

        $html = <<<HTML
            <p>Hola:</p>
            <p>Os recordamos que el cobro <strong>{$charge->title}</strong>
            correspondiente a <strong>{$member->full_name}</strong> por importe de
            <strong>{$amount} €</strong> está pendiente de pago
            (fecha límite: {$dueDate}).</p>
            <p>Si ya lo habéis abonado, podéis ignorar este mensaje.</p>
            <p>Gracias,<br>El equipo del grupo scout.</p>
        HTML;

        return new Content(htmlString: $html);
    }
}
