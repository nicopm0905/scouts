<?php

use App\Http\Controllers\Secretary\DocumentController;
use App\Http\Controllers\Secretary\MinuteController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('documents', DocumentController::class)
        ->only(['index', 'store', 'update', 'destroy']);

    Route::resource('minutes', MinuteController::class)
        ->only(['index', 'store', 'update', 'destroy']);
});
