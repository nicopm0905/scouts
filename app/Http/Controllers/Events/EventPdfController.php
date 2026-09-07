<?php

namespace App\Http\Controllers\Events;

use App\Enums\ChargeStatus;
use App\Http\Controllers\Controller;
use App\Models\ChargeMember;
use App\Models\Event;
use App\Models\EventMember;
use App\Models\User;
use App\Services\Events\EventMaterialList;
use App\Services\Events\MscOutingSheet;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EventPdfController extends Controller
{
    use AuthorizesRequests;

    /** Listado de asistentes: nombre, teléfono de contacto y datos médicos resumidos. */
    public function attendees(Request $request, Event $event): Response
    {
        $this->authorize('view', $event);

        // Datos sensibles (alergias/medicación de menores): solo secretaría/admin
        // (members.sensitive) o responsables con ámbito sobre alguna rama del evento.
        abort_unless($this->canSeeAttendeeHealthData($request->user(), $event), 403);

        $paymentByMember = $this->paymentStatusByMember($event);

        $rows = $event->enrollments()
            ->where('enrolled', true)
            ->with(['member.healthRecord', 'member.families'])
            ->get()
            ->map(function ($enrollment) use ($paymentByMember) {
                $member = $enrollment->member;
                $family = $member?->families?->first();

                return [
                    'name' => $member?->full_name,
                    'role_label' => $member?->role?->label(),
                    'phone' => $family?->contact_phone ?? $member?->phone,
                    'health_summary' => $this->healthSummary($member?->healthRecord),
                    'payment_label' => $paymentByMember?->get($member?->id),
                ];
            })
            ->sortBy('name')
            ->values();

        activity()
            ->causedBy($request->user())
            ->performedOn($event)
            ->log('Descarga del PDF de asistentes (incluye datos médicos)');

        $pdf = Pdf::loadView('pdf.event-attendees', [
            'event' => $event,
            'rows' => $rows,
            'showPayment' => $paymentByMember !== null,
        ]);

        $filename = Str::slug($event->title).'-listado-asistentes.pdf';

        return $pdf->stream($filename);
    }

    /** Circular informativa: fechas, precio, material y enlace de inscripción por familia. */
    public function circular(Event $event): Response
    {
        // Contiene los tokens públicos de inscripción de todas las familias:
        // solo quien puede gestionar el evento debe generarla.
        $this->authorize('update', $event);

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

    /** Documento oficial de Autorización individual (Asidonia-Jerez). */
    public function authorization(Request $request, Event $event, EventMember $enrollment): Response
    {
        abort_unless($enrollment->event_id === $event->id, 404);

        $member = $enrollment->member;
        $member?->load(['healthRecord', 'families']);

        $family = $member?->families?->first();
        $healthRecord = $member?->healthRecord;

        $healthParts = array_filter([
            $healthRecord?->allergies ? 'Alergias: '.$healthRecord->allergies : null,
            $healthRecord?->intolerances ? 'Intolerancias: '.$healthRecord->intolerances : null,
            $healthRecord?->medication ? 'Medicación: '.$healthRecord->medication : null,
        ]);
        $defaultHealth = $healthParts ? implode(' · ', $healthParts) : 'Sin alergias ni atenciones especiales registradas.';

        $pdf = Pdf::loadView('pdf.event-authorization', [
            'event' => $event,
            'member' => $member,
            'enrollment' => $enrollment,
            'healthRecord' => $healthRecord,
            'health_summary' => $enrollment->health_summary ?: $defaultHealth,
            'family_name' => $enrollment->family_name ?: $family?->name,
            'family_dni' => $enrollment->family_dni ?: ($family?->dni ?? $family?->document_number),
            'address' => $enrollment->address ?: ($member?->address ?? '___________________'),
            'contact_phone' => $enrollment->contact_phone ?: ($family?->contact_phone ?? $member?->phone),
        ]);

        $filename = Str::slug($event->title).'-autorizacion-'.Str::slug($member?->full_name ?? 'scout').'.pdf';

        return $pdf->stream($filename);
    }

    /** Descargar paquete ZIP con TODAS las autorizaciones firmadas del evento. */
    public function downloadZip(Request $request, Event $event): Response
    {
        $this->authorize('view', $event);

        $enrollments = $event->enrollments()
            ->where(function ($q) {
                $q->where('enrolled', true)
                    ->orWhereNotNull('confirmed_at')
                    ->orWhereNotNull('signature_data')
                    ->orWhereNotNull('authorization_file_id');
            })
            ->with(['member.healthRecord', 'member.families'])
            ->get();

        $zipFileName = Str::slug($event->title).'-autorizaciones.zip';
        $tempDir = storage_path('app/temp');
        if (! file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        $zipPath = $tempDir.'/'.Str::random(16).'.zip';

        $zip = new \ZipArchive;
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            abort(500, 'No se pudo crear el archivo ZIP.');
        }

        foreach ($enrollments as $enrollment) {
            $member = $enrollment->member;
            if (! $member) {
                continue;
            }

            $family = $member->families?->first();
            $healthRecord = $member->healthRecord;

            $healthParts = array_filter([
                $healthRecord?->allergies ? 'Alergias: '.$healthRecord->allergies : null,
                $healthRecord?->intolerances ? 'Intolerancias: '.$healthRecord->intolerances : null,
                $healthRecord?->medication ? 'Medicación: '.$healthRecord->medication : null,
            ]);
            $defaultHealth = $healthParts ? implode(' · ', $healthParts) : 'Sin alergias ni atenciones especiales registradas.';

            $pdf = Pdf::loadView('pdf.event-authorization', [
                'event' => $event,
                'member' => $member,
                'enrollment' => $enrollment,
                'healthRecord' => $healthRecord,
                'health_summary' => $enrollment->health_summary ?: $defaultHealth,
                'family_name' => $enrollment->family_name ?: $family?->name,
                'family_dni' => $enrollment->family_dni ?: ($family?->dni ?? $family?->document_number),
                'address' => $enrollment->address ?: ($member?->address ?? '___________________'),
                'contact_phone' => $enrollment->contact_phone ?: ($family?->contact_phone ?? $member->phone),
            ]);

            $pdfContent = $pdf->output();
            $fileNameInZip = 'Autorizacion_'.Str::slug($member->full_name).'.pdf';
            $zip->addFromString($fileNameInZip, $pdfContent);
        }

        $zip->close();

        return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
    }

    /**
     * ¿Puede el usuario ver los datos médicos del listado de asistentes?
     * Sí si tiene members.sensitive (secretaría/admin) o si es un usuario
     * con ámbito de rama (responsable) sobre alguna rama del evento.
     */
    private function canSeeAttendeeHealthData(User $user, Event $event): bool
    {
        if ($user->can('members.sensitive') || $user->canSeeAllBranches()) {
            return true;
        }

        return (bool) array_intersect($event->branches ?? [], $user->branches ?? []);
    }

    /**
     * Estado de pago por miembro de los cobros ligados al evento.
     * Devuelve null si el evento no tiene cobros asociados.
     *
     * @return Collection<int, string>|null mapa member_id => etiqueta
     */
    private function paymentStatusByMember(Event $event)
    {
        $chargeIds = $event->charges()->pluck('id');

        if ($chargeIds->isEmpty()) {
            return null;
        }

        return ChargeMember::query()
            ->whereIn('charge_id', $chargeIds)
            ->get()
            ->groupBy('member_id')
            ->map(function ($assignments) {
                if ($assignments->contains(fn (ChargeMember $a) => $a->status === ChargeStatus::Pending)) {
                    return ChargeStatus::Pending->label();
                }

                if ($assignments->every(fn (ChargeMember $a) => $a->status === ChargeStatus::Exempt)) {
                    return ChargeStatus::Exempt->label();
                }

                return ChargeStatus::Paid->label();
            });
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

    /** Exportar el calendario a PDF */
    public function calendar(Request $request): Response
    {
        $this->authorize('viewAny', Event::class);

        $query = Event::query()->orderBy('start_at');

        $branchName = 'Todas las ramas';
        if ($request->filled('branch')) {
            $branch = $request->string('branch')->toString();
            $query->whereJsonContains('branches', $branch);
            $branchName = ucfirst($branch);
        }

        $events = $query->get();

        $pdf = Pdf::loadView('pdf.calendar', [
            'events' => $events,
            'branchName' => $branchName,
        ])->setPaper('a4', 'landscape');

        $filename = 'calendario-eventos-'.Str::slug($branchName).'.pdf';

        return $pdf->stream($filename);
    }

    /** Lista de material agregada de todas las actividades del evento (para empaquetar / reservar). */
    public function materials(Event $event, EventMaterialList $list): Response
    {
        $this->authorize('view', $event);

        $pdf = Pdf::loadView('pdf.event-materials', [
            'event' => $event,
            'list' => $list->for($event),
        ]);

        return $pdf->stream(Str::slug($event->title).'-material.pdf');
    }

    /**
     * Ficha de salida oficial de la delegación: datos de la salida, los tres
     * ámbitos del plan de rama y la rejilla de estructura por día y franja.
     */
    public function mscOuting(Event $event, MscOutingSheet $sheets): Response
    {
        $this->authorize('view', $event);

        $pdf = Pdf::loadView('pdf.event-msc-outing', [
            'event' => $event,
            'sheet' => $sheets->for($event),
        ])->setPaper('a4');

        return $pdf->stream(Str::slug($event->title).'-ficha-salida.pdf');
    }

    /** Exportar Dossier de Campamento/Evento */
    public function dossier(Event $event, EventMaterialList $materialList): \Illuminate\Http\Response
    {
        $this->authorize('view', $event);

        $event->load([
            'activities.objectives.branchPlan',
            'activities.materials.inventoryItem',
            'activities' => fn ($q) => $q->orderBy('day_number')->orderBy('time_slot')->orderBy('activity_number'),
        ]);

        $pdf = Pdf::loadView('pdf.event-dossier', [
            'event' => $event,
            'materialTotals' => $materialList->for($event)['items'],
        ])->setPaper('a4');

        $filename = Str::slug($event->title).'-dossier.pdf';

        return $pdf->stream($filename);
    }
}
