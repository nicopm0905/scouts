<?php

use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

/*
| Gestión de cuentas de acceso (coordinación). Permiso 'users.manage'.
*/
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('usuarios', [UserController::class, 'index'])->name('users.index');
    Route::post('usuarios', [UserController::class, 'store'])->name('users.store');
    Route::patch('usuarios/{user}', [UserController::class, 'update'])->name('users.update');
    Route::post('usuarios/{user}/reinvitar', [UserController::class, 'resendInvite'])->name('users.resend-invite');
    Route::delete('usuarios/{user}', [UserController::class, 'destroy'])->name('users.destroy');
});
