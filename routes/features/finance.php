<?php

use App\Http\Controllers\Finance\BudgetController;
use App\Http\Controllers\Finance\ChargeController;
use App\Http\Controllers\Finance\ChargeMemberController;
use App\Http\Controllers\Finance\FinanceReportController;
use App\Http\Controllers\Finance\InvoiceController;
use App\Http\Controllers\Finance\SettingsController;
use App\Http\Controllers\Finance\TreasuryDashboardController;
use Illuminate\Support\Facades\Route;

/*
| Rutas del feature de Tesorería (Agente B): cobros, facturas, informe
| económico, recordatorios y ajustes. Todas autenticadas.
*/
Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard de Tesorería
    Route::get('/tesoreria', [TreasuryDashboardController::class, 'index'])->name('finance.dashboard');
    // Cobros
    Route::get('/cobros', [ChargeController::class, 'index'])->name('charges.index');
    Route::post('/cobros', [ChargeController::class, 'store'])->name('charges.store');
    Route::get('/cobros/{charge}', [ChargeController::class, 'show'])->name('charges.show');
    Route::post('/cobros/{charge}/recordar', [ChargeController::class, 'remind'])->name('charges.remind');
    Route::post('/cobros/{charge}/marcar-pagados', [ChargeController::class, 'bulkMarkPaid'])->name('charges.members.bulk-mark-paid');
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

    // Presupuestos
    Route::post('/presupuestos', [BudgetController::class, 'store'])->name('budgets.store');
    Route::get('/presupuestos/{budget}', [BudgetController::class, 'show'])->name('budgets.show');
    Route::put('/presupuestos/{budget}', [BudgetController::class, 'update'])->name('budgets.update');
    Route::post('/presupuestos/{budget}/items', [BudgetController::class, 'storeItem'])->name('budgets.items.store');
    Route::put('/presupuestos/items/{budgetItem}', [BudgetController::class, 'updateItem'])->name('budgets.items.update');
    Route::delete('/presupuestos/items/{budgetItem}', [BudgetController::class, 'destroyItem'])->name('budgets.items.destroy');

    // Ajustes
    Route::get('/ajustes', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/ajustes', [SettingsController::class, 'update'])->name('settings.update');
});
