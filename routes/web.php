<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
| La portada pública ('/') la registra routes/features/site.php.
| Esta ruta es la presentación de la plataforma de gestión para responsables.
*/
Route::get('/plataforma', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('platform');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
| Autocarga de rutas por feature. Cada agente crea su propio fichero en
| routes/features/<feature>.php y NO edita este archivo (evita conflictos).
| Las rutas autenticadas van dentro del grupo 'auth'; las públicas tokenizadas
| pueden registrarse fuera dentro del propio fichero del feature.
*/
foreach (glob(__DIR__.'/features/*.php') as $featureRoutes) {
    require $featureRoutes;
}

require __DIR__.'/auth.php';
