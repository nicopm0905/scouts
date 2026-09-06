<?php

namespace App\Mail;

use App\Models\Signature;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

/** Solicitud de firma digital de un documento (Consent o Document) enviada a la familia. */
class SignatureRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Signature $signature) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Firma solicitada: '.$this->signature->title(),
        );
    }

    public function content(): Content
    {
        $member = $this->signature->member;
        $title = $this->signature->title();
        $url = URL::route('public.signature.show', ['token' => $this->signature->public_token]);

        $html = <<<HTML
            <p>Hola:</p>
            <p>Os pedimos que firméis el siguiente documento de <strong>{$member->full_name}</strong>:
            <strong>{$title}</strong>.</p>
            <p>Podéis revisarlo y firmarlo digitalmente desde este enlace, sin necesidad de imprimirlo:</p>
            <p><a href="{$url}">{$url}</a></p>
            <p>Gracias,<br>El equipo del grupo scout.</p>
        HTML;

        return new Content(htmlString: $html);
    }
}
