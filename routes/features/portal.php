<?php

use App\Http\Controllers\Portal\CalendarController;
use App\Http\Controllers\Portal\ChildController;
use App\Http\Controllers\Portal\DashboardController;
use App\Http\Controllers\Portal\PaymentController;
use Illuminate\Support\Facades\Route;

/*
| Portal de familias. Prefijo /portal, solo cuentas con rol "familia".
| Todo lo que se sirve aquí está acotado a los scouts a cargo de la cuenta
| (User::children()); no se usan permisos de módulo.
*/
Route::middleware(['auth', 'verified', 'role:familia'])
    ->prefix('portal')
    ->name('portal.')
    ->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::get('/pagos', [PaymentController::class, 'index'])->name('payments');
        Route::get('/calendario', [CalendarController::class, 'index'])->name('calendar');
        Route::post('/calendario/ical', [CalendarController::class, 'regenerateToken'])->name('calendar.ical.regenerate');
        Route::get('/scouts/{member}', [ChildController::class, 'show'])->name('children.show');
        Route::post('/scouts/{member}/revision', [ChildController::class, 'submitReview'])->name('children.review');
    });
