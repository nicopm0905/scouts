<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\EventMember;
use App\Services\Drive\DriveServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Páginas públicas (sin autenticación) para que las familias confirmen la
 * inscripción de un evento y suban la autorización firmada. Acceso por
 * public_token individual (uno por miembro y evento).
 */
class EnrollmentController extends Controller
{
    public function show(string $token): Response
    {
        $enrollment = EventMember::where('public_token', $token)->with('event', 'member')->firstOrFail();

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
        ]);
    }

    /** La familia confirma la inscripción de su hijo/a al evento. */
    public function confirm(string $token): RedirectResponse
    {
        $enrollment = EventMember::where('public_token', $token)->firstOrFail();

        $enrollment->update([
            'enrolled' => true,
            'confirmed_at' => now(),
        ]);

        return back()->with('success', 'Inscripción confirmada. Gracias.');
    }

    /** Sube la autorización firmada (PDF/foto) al DriveService. */
    public function uploadAuthorization(Request $request, string $token, DriveServiceInterface $drive): RedirectResponse
    {
        $enrollment = EventMember::where('public_token', $token)->with('event')->firstOrFail();

        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        $driveFile = $drive->upload(
            $request->file('file'),
            null,
            'autorizacion-'.$enrollment->event_id.'-'.$enrollment->member_id.'.'.$request->file('file')->getClientOriginalExtension()
        );

        $enrollment->update(['authorization_file_id' => $driveFile->id]);

        return back()->with('success', 'Autorización subida correctamente.');
    }
}
