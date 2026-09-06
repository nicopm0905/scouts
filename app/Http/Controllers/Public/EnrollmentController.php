<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\EventMember;
use App\Services\Drive\DriveServiceInterface;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Páginas públicas (sin autenticación) para que las familias confirmen la
 * inscripción de un evento y suban la autorización firmada. Acceso por
 * public_token individual (uno por miembro y evento).
 */
class EnrollmentController extends Controller
{
    public function show(string $token): Response
    {
        $enrollment = EventMember::where('public_token', $token)
            ->with(['event', 'member.healthRecord', 'member.families'])
            ->firstOrFail();

        $family = $enrollment->member->families?->first();
        $healthRecord = $enrollment->member->healthRecord;

        $healthParts = array_filter([
            $healthRecord?->allergies ? 'Alergias: '.$healthRecord->allergies : null,
            $healthRecord?->intolerances ? 'Intolerancias: '.$healthRecord->intolerances : null,
            $healthRecord?->medication ? 'Medicación: '.$healthRecord->medication : null,
        ]);
        $defaultHealth = $healthParts ? implode(' · ', $healthParts) : '';

        return Inertia::render('Public/Enrollment', [
            'token' => $token,
            'event' => [
                'title' => $enrollment->event->title,
                'type_label' => $enrollment->event->type->label(),
                'start_at' => $enrollment->event->start_at->toIso8601String(),
                'end_at' => $enrollment->event->end_at?->toIso8601String(),
                'location' => $enrollment->event->location,
                'description' => $enrollment->event->description,
            ],
            'member_name' => $enrollment->member->full_name,
            'enrolled' => $enrollment->enrolled,
            'confirmed_at' => $enrollment->confirmed_at?->toIso8601String(),
            'has_authorization' => $enrollment->hasAuthorization(),
            'signature_data' => $enrollment->signature_data,
            'medical_consent' => (bool) ($enrollment->medical_consent ?? true),
            'image_consent' => (bool) ($enrollment->image_consent ?? true),
            'family_name' => $enrollment->family_name ?? $family?->name ?? '',
            'family_dni' => $enrollment->family_dni ?? $family?->dni ?? $family?->document_number ?? '',
            'address' => $enrollment->address ?? $enrollment->member->address ?? '',
            'contact_phone' => $enrollment->contact_phone ?? $family?->contact_phone ?? $enrollment->member->phone ?? '',
            'health_summary' => $enrollment->health_summary ?? $defaultHealth,
            'declined' => str_starts_with($enrollment->notes ?? '', 'No asiste'),
        ]);
    }

    /** La familia confirma e introduce la firma digital y consentimientos del menor. */
    public function confirm(Request $request, string $token, DriveServiceInterface $drive): RedirectResponse
    {
        $enrollment = EventMember::where('public_token', $token)
            ->with(['event', 'member.healthRecord', 'member.families'])
            ->firstOrFail();

        $validated = $request->validate([
            'signature_data' => ['nullable', 'string'],
            'medical_consent' => ['nullable', 'boolean'],
            'image_consent' => ['nullable', 'boolean'],
            'family_name' => ['nullable', 'string', 'max:255'],
            'family_dni' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'health_summary' => ['nullable', 'string', 'max:1000'],
        ]);

        $enrollment->update([
            'enrolled' => true,
            'confirmed_at' => now(),
            'signature_data' => $validated['signature_data'] ?? $enrollment->signature_data,
            'medical_consent' => $validated['medical_consent'] ?? true,
            'image_consent' => $validated['image_consent'] ?? true,
            'family_name' => $validated['family_name'] ?? $enrollment->family_name,
            'family_dni' => $validated['family_dni'] ?? $enrollment->family_dni,
            'address' => $validated['address'] ?? $enrollment->address,
            'contact_phone' => $validated['contact_phone'] ?? $enrollment->contact_phone,
            'health_summary' => $validated['health_summary'] ?? $enrollment->health_summary,
        ]);

        // Si hay firma digital (ya sea nueva o guardada previamente), generamos el PDF y lo subimos
        if ($enrollment->signature_data) {
            $member = $enrollment->member;
            $family = $member?->families?->first();
            $healthRecord = $member?->healthRecord;

            $healthParts = array_filter([
                $healthRecord?->allergies ? 'Alergias: '.$healthRecord->allergies : null,
                $healthRecord?->intolerances ? 'Intolerancias: '.$healthRecord->intolerances : null,
                $healthRecord?->medication ? 'Medicación: '.$healthRecord->medication : null,
            ]);
            $defaultHealth = $healthParts ? implode(' · ', $healthParts) : 'Sin alergias ni atenciones especiales registradas.';

            $pdf = Pdf::loadView('pdf.event-authorization', [
                'event' => $enrollment->event,
                'member' => $member,
                'enrollment' => $enrollment,
                'healthRecord' => $healthRecord,
                'health_summary' => $enrollment->health_summary ?: $defaultHealth,
                'family_name' => $enrollment->family_name ?: $family?->name,
                'family_dni' => $enrollment->family_dni ?: ($family?->dni ?? $family?->document_number),
                'address' => $enrollment->address ?: ($member?->address ?? '___________________'),
                'contact_phone' => $enrollment->contact_phone ?: ($family?->contact_phone ?? $member?->phone),
            ]);

            $filename = "{$member->full_name} - {$enrollment->event->title}.pdf";

            $driveFile = $drive->uploadRaw(
                $pdf->output(),
                $filename,
                'application/pdf',
                $enrollment->event->drive_folder_id
            );

            $enrollment->update(['authorization_file_id' => $driveFile->id]);
        }

        return back()->with('success', 'Inscripción y autorización firmada correctamente. ¡Muchas gracias!');
    }

    /** La familia avisa de que el participante no asistirá a la actividad. */
    public function decline(Request $request, string $token): RedirectResponse
    {
        $enrollment = EventMember::where('public_token', $token)->firstOrFail();

        $enrollment->update([
            'notes' => 'No asiste: '.($request->reason ?? 'Aviso desde el portal de familias.'),
            'enrolled' => false,
        ]);

        return back()->with('success', 'Gracias por avisar de que no asiste. ¡Lo tendremos en cuenta!');
    }

    /** Sube la autorización firmada (PDF/foto) al DriveService. */
    public function uploadAuthorization(Request $request, string $token, DriveServiceInterface $drive): RedirectResponse
    {
        $enrollment = EventMember::where('public_token', $token)->with('event')->firstOrFail();

        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        $extension = $request->file('file')->getClientOriginalExtension();
        $filename = "{$enrollment->member->full_name} - {$enrollment->event->title}.{$extension}";

        $driveFile = $drive->upload(
            $request->file('file'),
            $enrollment->event->drive_folder_id, // Usar la carpeta del evento si existe
            $filename
        );

        $enrollment->update(['authorization_file_id' => $driveFile->id]);

        return back()->with('success', 'Autorización subida correctamente.');
    }

    /** Descarga o visualiza la Autorización Oficial en PDF para la familia. */
    public function downloadPdf(string $token): SymfonyResponse
    {
        $enrollment = EventMember::where('public_token', $token)
            ->with(['event', 'member.healthRecord', 'member.families'])
            ->firstOrFail();

        $member = $enrollment->member;
        $family = $member?->families?->first();
        $healthRecord = $member?->healthRecord;

        $healthParts = array_filter([
            $healthRecord?->allergies ? 'Alergias: '.$healthRecord->allergies : null,
            $healthRecord?->intolerances ? 'Intolerancias: '.$healthRecord->intolerances : null,
            $healthRecord?->medication ? 'Medicación: '.$healthRecord->medication : null,
        ]);
        $defaultHealth = $healthParts ? implode(' · ', $healthParts) : 'Sin alergias ni atenciones especiales registradas.';

        $pdf = Pdf::loadView('pdf.event-authorization', [
            'event' => $enrollment->event,
            'member' => $member,
            'enrollment' => $enrollment,
            'healthRecord' => $healthRecord,
            'health_summary' => $enrollment->health_summary ?: $defaultHealth,
            'family_name' => $enrollment->family_name ?: $family?->name,
            'family_dni' => $enrollment->family_dni ?: ($family?->dni ?? $family?->document_number),
            'address' => $enrollment->address ?: ($member?->address ?? '___________________'),
            'contact_phone' => $enrollment->contact_phone ?: ($family?->contact_phone ?? $member?->phone),
        ]);

        $filename = Str::slug($enrollment->event->title).'-autorizacion-'.Str::slug($member?->full_name ?? 'scout').'.pdf';

        return $pdf->stream($filename);
    }
}
