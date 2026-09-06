<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

/**
 * Resumen diario de caducidades para secretaría/administración: documentos del grupo,
 * certificados de delitos sexuales, titulaciones de monitores e inventario por revisar.
 * Se envía UN único email agrupado (no uno por elemento).
 */
class ExpiryAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  Collection  $documents  Document próximos a caducar
     * @param  Collection  $certificates  LeaderProfile con certificado caducado/por caducar (con member)
     * @param  Collection  $trainings  LeaderTraining caducadas/por caducar (con leaderProfile.member)
     */
    public function __construct(
        public Collection $documents,
        public Collection $certificates,
        public Collection $trainings,
        public int $inventoryCount,
        public int $days,
    ) {}

    public function envelope(): Envelope
    {
        $total = $this->documents->count()
            + $this->certificates->count()
            + $this->trainings->count()
            + $this->inventoryCount;

        return new Envelope(
            subject: "Avisos de caducidad: {$total} elementos requieren atención",
        );
    }

    public function content(): Content
    {
        $sections = [];

        if ($this->documents->isNotEmpty()) {
            $items = $this->documents->map(function ($doc) {
                $date = $doc->expires_at?->format('d/m/Y') ?? '—';

                return "<li><strong>{$doc->title}</strong> — caduca el {$date}</li>";
            })->implode('');
            $sections[] = "<h3>Documentos del grupo ({$this->documents->count()})</h3><ul>{$items}</ul>";
        }

        if ($this->certificates->isNotEmpty()) {
            $items = $this->certificates->map(function ($profile) {
                $name = $profile->member?->full_name ?? "Perfil #{$profile->id}";
                $date = $profile->sexual_offenses_certificate_expires_at?->format('d/m/Y') ?? '—';

                return "<li><strong>{$name}</strong> — certificado de delitos sexuales caduca el {$date}</li>";
            })->implode('');
            $sections[] = "<h3>Certificados de delitos sexuales ({$this->certificates->count()})</h3><ul>{$items}</ul>";
        }

        if ($this->trainings->isNotEmpty()) {
            $items = $this->trainings->map(function ($training) {
                $name = $training->leaderProfile?->member?->full_name ?? '—';
                $date = $training->expires_at?->format('d/m/Y') ?? '—';
                $status = $training->isExpired() ? 'caducada desde' : 'caduca el';

                return "<li><strong>{$name}</strong> — {$training->name}, {$status} {$date}</li>";
            })->implode('');
            $sections[] = "<h3>Titulaciones de monitores ({$this->trainings->count()})</h3><ul>{$items}</ul>";
        }

        if ($this->inventoryCount > 0) {
            $sections[] = "<h3>Inventario</h3><p>Hay <strong>{$this->inventoryCount}</strong> ítems pendientes de revisión.</p>";
        }

        $body = implode('', $sections);

        $html = <<<HTML
            <p>Hola:</p>
            <p>Este es el resumen de caducidades próximas (ventana de {$this->days} días)
            detectadas en la plataforma del grupo:</p>
            {$body}
            <p>Puedes consultar el detalle en el panel de control de la plataforma.</p>
            <p>Gracias,<br>El equipo del grupo scout.</p>
        HTML;

        return new Content(htmlString: $html);
    }
}
