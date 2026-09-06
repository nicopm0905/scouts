<?php

use App\Http\Controllers\Events\EventChargeController;
use App\Http\Controllers\Events\EventChecklistController;
use App\Http\Controllers\Events\EventController;
use App\Http\Controllers\Events\EventEnrollmentController;
use App\Http\Controllers\Events\EventPdfController;
use App\Http\Controllers\Public\EnrollmentController;
use App\Http\Controllers\Public\IcalController;
use Illuminate\Support\Facades\Route;

/*
| Agente C — Calendario y eventos.
| Rutas autenticadas bajo /eventos y públicas tokenizadas bajo /publico/*.
*/

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/eventos', [EventController::class, 'index'])->name('events.index');
    Route::get('/eventos/crear', [EventController::class, 'create'])->name('events.create');
    Route::post('/eventos', [EventController::class, 'store'])->name('events.store');
    Route::get('/eventos/{event}', [EventController::class, 'show'])->name('events.show');
    Route::get('/eventos/{event}/editar', [EventController::class, 'edit'])->name('events.edit');
    Route::put('/eventos/{event}', [EventController::class, 'update'])->name('events.update');
    Route::delete('/eventos/{event}', [EventController::class, 'destroy'])->name('events.destroy');

    Route::post('/eventos/ical/regenerar', [EventController::class, 'regenerateIcalToken'])->name('events.ical.regenerate');

    Route::post('/eventos/{event}/inscripciones', [EventEnrollmentController::class, 'store'])
        ->name('events.enrollments.store');
    Route::patch('/eventos/{event}/inscripciones/{enrollment}', [EventEnrollmentController::class, 'update'])
        ->name('events.enrollments.update');

    Route::post('/eventos/{event}/checklist', [EventChecklistController::class, 'store'])->name('events.checklist.store');
    Route::patch('/eventos/{event}/checklist/{item}', [EventChecklistController::class, 'update'])->name('events.checklist.update');
    Route::delete('/eventos/{event}/checklist/{item}', [EventChecklistController::class, 'destroy'])->name('events.checklist.destroy');

    Route::post('/eventos/{event}/cobros', [EventChargeController::class, 'store'])->name('events.charges.store');

    Route::get('/eventos/{event}/pdf/asistentes', [EventPdfController::class, 'attendees'])->name('events.pdf.attendees');
    Route::get('/eventos/{event}/pdf/circular', [EventPdfController::class, 'circular'])->name('events.pdf.circular');
    Route::get('/eventos/{event}/pdf/autorizacion/{enrollment}', [EventPdfController::class, 'authorization'])->name('events.pdf.authorization');
    Route::get('/eventos/{event}/pdf/zip', [EventPdfController::class, 'downloadZip'])->name('events.pdf.zip');
    Route::get('/eventos/{event}/pdf/dossier', [EventPdfController::class, 'dossier'])->name('events.pdf.dossier');
    Route::get('/eventos/{event}/pdf/material', [EventPdfController::class, 'materials'])->name('events.pdf.materials');

    // Exportación del calendario a PDF
    Route::get('/eventos-calendario/pdf', [EventPdfController::class, 'calendar'])->name('events.pdf.calendar');
});

// Rutas públicas tokenizadas — SIN middleware auth.
Route::get('/publico/inscripcion/{token}', [EnrollmentController::class, 'show'])->name('public.enrollment.show');
Route::get('/publico/inscripcion/{token}/pdf', [EnrollmentController::class, 'downloadPdf'])->name('public.enrollment.pdf');
Route::post('/publico/inscripcion/{token}/confirmar', [EnrollmentController::class, 'confirm'])->name('public.enrollment.confirm');
Route::post('/publico/inscripcion/{token}/declinar', [EnrollmentController::class, 'decline'])->name('public.enrollment.decline');
Route::post('/publico/inscripcion/{token}/autorizacion', [EnrollmentController::class, 'uploadAuthorization'])->name('public.enrollment.upload');

Route::get('/publico/calendario/{token}', [IcalController::class, 'show'])->name('public.ical.show');
