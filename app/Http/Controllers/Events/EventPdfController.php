<?php

namespace App\Http\Controllers\Events;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EventPdfController extends Controller
{
    use AuthorizesRequests;

    /** Listado de asistentes: nombre, teléfono de contacto y datos médicos resumidos. */
    public function attendees(Event $event): Response
    {
        $this->authorize('view', $event);

        $rows = $event->enrollments()
            ->where('enrolled', true)
            ->with(['member.healthRecord', 'member.families'])
            ->get()
            ->map(function ($enrollment) {
                $member = $enrollment->member;
                $family = $member?->families?->first();

                return [
                    'name' => $member?->full_name,
                    'role_label' => $member?->role?->label(),
                    'phone' => $family?->contact_phone ?? $member?->phone,
                    'health_summary' => $this->healthSummary($member?->healthRecord),
                ];
            })
            ->sortBy('name')
            ->values();

        $pdf = Pdf::loadView('pdf.event-attendees', [
            'event' => $event,
            'rows' => $rows,
        ]);

        $filename = Str::slug($event->title).'-listado-asistentes.pdf';

        return $pdf->stream($filename);
    }

    /** Circular informativa: fechas, precio, material y enlace de inscripción por familia. */
    public function circular(Event $event): Response
    {
        $this->authorize('view', $event);

        $price = $event->charges()->orderByDesc('created_at')->value('amount');

        $links = $event->enrollments()
            ->with('member')
            ->get()
            ->map(fn ($enrollment) => [
                'name' => $enrollment->member?->full_name,
                'url' => URL::route('public.enrollment.show', ['token' => $enrollment->public_token]),
            ])
            ->sortBy('name')
            ->values();

        $pdf = Pdf::loadView('pdf.event-circular', [
            'event' => $event,
            'price' => $price,
            'links' => $links,
        ]);

        $filename = Str::slug($event->title).'-circular.pdf';

        return $pdf->stream($filename);
    }

    private function healthSummary($healthRecord): string
    {
        if (! $healthRecord) {
            return 'Sin datos médicos registrados.';
        }

        $parts = array_filter([
            $healthRecord->allergies ? 'Alergias: '.$healthRecord->allergies : null,
            $healthRecord->intolerances ? 'Intolerancias: '.$healthRecord->intolerances : null,
            $healthRecord->medication ? 'Medicación: '.$healthRecord->medication : null,
        ]);

        return $parts ? implode(' · ', $parts) : 'Sin incidencias.';
    }
}
