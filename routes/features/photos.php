<?php

use App\Http\Controllers\Photos\AlbumController;
use App\Http\Controllers\Photos\HistoryEntryController;
use App\Http\Controllers\Photos\PhotoController;
use App\Http\Controllers\Public\HistoryPublicController;
use Illuminate\Support\Facades\Route;

/*
| Fotos e historia del grupo.
| - Álbumes/fotos: gestión autenticada + galería pública de álbumes 'publishable'.
| - Historia: timeline gestionada por secretaría (history.manage) + página pública /historia.
*/

// Páginas públicas (sin auth): respetan el consentimiento de imagen (solo contenido publicable).
Route::get('/galeria', [AlbumController::class, 'publicIndex'])->name('albums.public');
Route::get('/historia', [HistoryPublicController::class, 'show'])->name('history.public');

Route::middleware('auth')->group(function () {
    Route::resource('albums', AlbumController::class)->except(['create', 'edit']);

    Route::post('albums/{album}/photos', [PhotoController::class, 'store'])->name('albums.photos.store');
    Route::delete('photos/{photo}', [PhotoController::class, 'destroy'])->name('photos.destroy');

    Route::resource('history-management', HistoryEntryController::class)
        ->except(['create', 'edit', 'show'])
        ->parameters(['history-management' => 'history_entry'])
        ->names('history');
});
