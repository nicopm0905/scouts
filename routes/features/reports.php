<?php

use App\Http\Controllers\Reports\AnnualReportController;
use Illuminate\Support\Facades\Route;

/*
| Informes de cierre de curso. Autenticado; el alcance por rama lo aplica el
| controlador (un responsable solo ve sus ramas).
*/
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/memoria', [AnnualReportController::class, 'index'])->name('reports.annual');
    Route::get('/memoria/pdf', [AnnualReportController::class, 'pdf'])->name('reports.annual.pdf');
});
