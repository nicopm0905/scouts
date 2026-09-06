<?php

use App\Http\Controllers\Members\SignatureController;
use App\Http\Controllers\Public\SignatureController as PublicSignatureController;
use Illuminate\Support\Facades\Route;

/*
| Feature: Firma digital de Consent/Document por email (sin login para la familia).
| Registrado automáticamente por routes/web.php dentro del grupo 'auth'.
*/
Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('members/{member}/consents/{type}/send-signature', [SignatureController::class, 'sendConsent'])
        ->name('signatures.consent.send');
    Route::post('documents/{document}/signatures', [SignatureController::class, 'sendDocument'])
        ->name('signatures.document.send');
});

// Público, sin auth — la familia firma desde el enlace del email.
Route::get('/publico/firma/{token}', [PublicSignatureController::class, 'show'])->name('public.signature.show');
Route::post('/publico/firma/{token}', [PublicSignatureController::class, 'sign'])->name('public.signature.sign');
