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

Route::middleware('auth')->group(function () {
    Route::get('/eventos', [EventController::class, 'index'])->name('events.index');
    Route::get('/eventos/crear', [EventController::class, 'create'])->name('events.create');
    Route::post('/eventos', [EventController::class, 'store'])->name('events.store');
    Route::get('/eventos/{event}', [EventController::class, 'show'])->name('events.show');
    Route::get('/eventos/{event}/editar', [EventController::class, 'edit'])->name('events.edit');
    Route::put('/eventos/{event}', [EventController::class, 'update'])->name('events.update');
    Route::delete('/eventos/{event}', [EventController::class, 'destroy'])->name('events.destroy');

    Route::post('/eventos/ical/regenerar', [EventController::class, 'regenerateIcalToken'])->name('events.ical.regenerate');

    Route::patch('/eventos/{event}/inscripciones/{enrollment}', [EventEnrollmentController::class, 'update'])
        ->name('events.enrollments.update');

    Route::post('/eventos/{event}/checklist', [EventChecklistController::class, 'store'])->name('events.checklist.store');
    Route::patch('/eventos/{event}/checklist/{item}', [EventChecklistController::class, 'update'])->name('events.checklist.update');
    Route::delete('/eventos/{event}/checklist/{item}', [EventChecklistController::class, 'destroy'])->name('events.checklist.destroy');

    Route::post('/eventos/{event}/cobros', [EventChargeController::class, 'store'])->name('events.charges.store');

    Route::get('/eventos/{event}/pdf/asistentes', [EventPdfController::class, 'attendees'])->name('events.pdf.attendees');
    Route::get('/eventos/{event}/pdf/circular', [EventPdfController::class, 'circular'])->name('events.pdf.circular');
});

// Rutas públicas tokenizadas — SIN middleware auth.
Route::get('/publico/inscripcion/{token}', [EnrollmentController::class, 'show'])->name('public.enrollment.show');
Route::post('/publico/inscripcion/{token}/confirmar', [EnrollmentController::class, 'confirm'])->name('public.enrollment.confirm');
Route::post('/publico/inscripcion/{token}/autorizacion', [EnrollmentController::class, 'uploadAuthorization'])->name('public.enrollment.upload');

Route::get('/publico/calendario/{token}', [IcalController::class, 'show'])->name('public.ical.show');
