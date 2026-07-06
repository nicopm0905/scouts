<?php

use App\Http\Controllers\Finance\ChargeController;
use App\Http\Controllers\Finance\ChargeMemberController;
use App\Http\Controllers\Finance\FinanceReportController;
use App\Http\Controllers\Finance\InvoiceController;
use App\Http\Controllers\Finance\SettingsController;
use Illuminate\Support\Facades\Route;

/*
| Rutas del feature de Tesorería (Agente B): cobros, facturas, informe
| económico, recordatorios y ajustes. Todas autenticadas.
*/
Route::middleware('auth')->group(function () {
    // Cobros
    Route::get('/cobros', [ChargeController::class, 'index'])->name('charges.index');
    Route::post('/cobros', [ChargeController::class, 'store'])->name('charges.store');
    Route::get('/cobros/{charge}', [ChargeController::class, 'show'])->name('charges.show');
    Route::post('/cobros/{charge}/recordar', [ChargeController::class, 'remind'])->name('charges.remind');
    Route::delete('/cobros/{charge}', [ChargeController::class, 'destroy'])->name('charges.destroy');

    Route::post('/cobros/reparto/{chargeMember}/marcar-pagado', [ChargeMemberController::class, 'markPaid'])
        ->name('charges.members.mark-paid');
    Route::get('/cobros/miembros/{member}/historial', [ChargeMemberController::class, 'history'])
        ->name('charges.members.history');

    // Facturas
    Route::get('/facturas', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::post('/facturas', [InvoiceController::class, 'store'])->name('invoices.store');
    Route::post('/facturas/subir', [InvoiceController::class, 'upload'])->name('invoices.upload');
    Route::patch('/facturas/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update');
    Route::delete('/facturas/{invoice}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');
    Route::post('/facturas/{invoice}/pdf', [InvoiceController::class, 'generatePdf'])->name('invoices.generate-pdf');

    // Informe económico
    Route::get('/informe-economico', [FinanceReportController::class, 'index'])->name('finance.report');
    Route::get('/informe-economico/exportar', [FinanceReportController::class, 'export'])->name('finance.report.export');

    // Ajustes
    Route::get('/ajustes', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/ajustes', [SettingsController::class, 'update'])->name('settings.update');
});
